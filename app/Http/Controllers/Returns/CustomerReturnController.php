<?php

namespace App\Http\Controllers\Returns;

use App\Http\Controllers\Controller;
use App\Models\CustomerReturn;
use App\Models\CustomerReturnItem;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use App\Models\CashSession;
use App\Models\ReturnReason;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\Store;
use App\Services\AuditLogService;
use App\Services\ReferenceNumberService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerReturnController extends Controller
{
    public function index(Request $r)
    {
        $this->authorize('view customer return');
        $user = Auth::user();
        $stores = $user->hasAnyRole(['superadmin', 'owner', 'finance']) ? Store::orderBy('name')->get() : collect();

        $q = CustomerReturn::with(['store', 'reason', 'creator'])
            ->when($r->store_id, fn($q) => $q->where('store_id', $r->store_id))
            ->when($r->status, fn($q) => $q->where('status', $r->status))
            ->orderBy('created_at', 'desc');

        if (!$user->hasAnyRole(['superadmin', 'owner', 'finance', 'admin gudang'])) {
            $storeIds = $user->stores->pluck('id');
            $q->whereIn('store_id', $storeIds);
        }

        $returns = $q->paginate(20)->withQueryString();
        return view('returns.customer.index', compact('returns', 'stores'));
    }

    public function create()
    {
        $this->authorize('process customer return');
        $user = Auth::user();
        $store = $user->primaryStore();
        $reasons = ReturnReason::where('is_active', true)
            ->whereIn('type', ['customer', 'both'])->get();
        $recentSales = Sale::where('store_id', $store?->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)->get();

        return view('returns.customer.create', compact('store', 'reasons', 'recentSales'));
    }

    public function store(Request $r)
    {
        $this->authorize('process customer return');
        $user = Auth::user();
        $store = $user->primaryStore();

        if (!$store) {
            return back()->withInput()->with('error', 'Anda belum ditugaskan ke toko manapun. Hubungi administrator.');
        }

        $r->validate([
            'type' => 'nullable|in:refund,exchange',
            'return_reason_id' => 'required|exists:return_reasons,id',
            'sale_id' => 'nullable|exists:sales,id',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.condition' => 'required|in:good,damaged',
            // Barang pengganti (khusus exchange)
            'replacement_items' => 'required_if:type,exchange|array|min:1',
            'replacement_items.*.variant_id' => 'required_with:replacement_items|exists:product_variants,id',
            'replacement_items.*.qty' => 'required_with:replacement_items|integer|min:1',
            'replacement_items.*.unit_price' => 'required_with:replacement_items|numeric|min:0',
            // Detail refund
            'refund_method' => 'nullable|in:cash,transfer',
            'refund_bank_name' => 'nullable|string|max:100',
            'refund_bank_account' => 'nullable|string|max:100',
            'refund_account_holder' => 'nullable|string|max:150',
            'refund_proof' => 'nullable|image|max:4096',
        ]);

        $activeSession = CashSession::where('user_id', Auth::id())->where('status', 'open')->first();
        if (!$activeSession) {
            return redirect()->back()->withInput()->with('error', 'Gagal memproses retur! Anda harus membuka Sesi Kasir (Cash Session) terlebih dahulu untuk memproses refund.');
        }

        $type = $r->input('type', 'refund'); // default refund (backward-compat)

        // Nilai barang dikembalikan & pengganti
        $returnedValue = 0;
        foreach ($r->items as $row) {
            $returnedValue += (float) $row['unit_price'] * (int) $row['qty'];
        }
        $replacementValue = 0;
        if ($type === 'exchange') {
            foreach ((array) $r->replacement_items as $row) {
                $replacementValue += (float) $row['unit_price'] * (int) $row['qty'];
            }
        }
        $diff = $replacementValue - $returnedValue; // exchange: + customer bayar, - direfund

        // Refund ke customer: penuh (refund) atau hanya kelebihan (exchange diff<0)
        $refundToCustomer = $type === 'refund' ? $returnedValue : max(0, -$diff);

        // Validasi metode refund + rekening/bukti bila transfer
        $refundMethod = null;
        if ($refundToCustomer > 0) {
            $refundMethod = $r->refund_method;
            if (!in_array($refundMethod, ['cash', 'transfer'], true)) {
                return back()->withInput()->with('error', 'Pilih metode refund (cash/transfer).');
            }
            if ($refundMethod === 'transfer') {
                $r->validate([
                    'refund_bank_name' => 'required|string|max:100',
                    'refund_bank_account' => 'required|string|max:100',
                    'refund_account_holder' => 'required|string|max:150',
                    'refund_proof' => 'required|image|max:4096',
                ], [], [
                    'refund_bank_name' => 'nama bank',
                    'refund_bank_account' => 'nomor rekening',
                    'refund_account_holder' => 'nama pemilik rekening',
                    'refund_proof' => 'bukti transfer',
                ]);
            }
        }

        // Untuk exchange perlu metode CASH sebagai metode utama nota pengganti (agar masuk hitungan kas).
        $cashMethod = PaymentMethod::where('type', 'cash')->where('is_active', true)->first();
        if ($type === 'exchange' && !$cashMethod) {
            return back()->withInput()->with('error', 'Metode pembayaran tipe "cash" belum tersedia. Tambahkan dulu di Master Metode Bayar.');
        }

        try {
            $return = DB::transaction(function () use ($r, $type, $store, $activeSession, $returnedValue, $replacementValue, $diff, $refundToCustomer, $refundMethod, $cashMethod) {

                $proofPath = ($refundMethod === 'transfer' && $r->hasFile('refund_proof'))
                    ? $r->file('refund_proof')->store('refund_proofs', 'public')
                    : null;

                $return = CustomerReturn::create([
                    'return_no' => ReferenceNumberService::customerReturn(),
                    'sale_id' => $r->sale_id,
                    'store_id' => $store->id,
                    'return_reason_id' => $r->return_reason_id,
                    'type' => $type,
                    'status' => 'processed',
                    'notes' => $r->notes,
                    'processed_at' => now(),
                    'processed_by' => Auth::id(),
                    'created_by' => Auth::id(),
                    'cash_session_id' => $activeSession->id,
                    'refund_amount' => $returnedValue,
                    'refund_method' => $refundMethod,
                    'refund_bank_name' => $refundMethod === 'transfer' ? $r->refund_bank_name : null,
                    'refund_bank_account' => $refundMethod === 'transfer' ? $r->refund_bank_account : null,
                    'refund_account_holder' => $refundMethod === 'transfer' ? $r->refund_account_holder : null,
                    'refund_proof_path' => $proofPath,
                    'exchange_diff' => $type === 'exchange' ? $diff : 0,
                ]);

                // Barang dikembalikan → stok kembali (hanya kondisi baik)
                foreach ($r->items as $row) {
                    $variant = ProductVariant::findOrFail($row['variant_id']);
                    $qty = (int) $row['qty'];
                    $price = (float) $row['unit_price'];

                    CustomerReturnItem::create([
                        'customer_return_id' => $return->id,
                        'product_variant_id' => $variant->id,
                        'qty' => $qty,
                        'unit_price' => $price,
                        'subtotal' => $price * $qty,
                        'condition' => $row['condition'],
                    ]);

                    if ($row['condition'] === 'good') {
                        StockService::mutate($variant, 'store', $store->id, $qty, 'return',
                            "Retur konsumen {$return->return_no}", CustomerReturn::class, $return->id);
                    }
                }

                // EXCHANGE: buat nota pengganti (stok keluar + reward), tautkan ke retur.
                if ($type === 'exchange') {
                    $original = $r->sale_id ? Sale::find($r->sale_id) : null;

                    $replacementSale = Sale::create([
                        'sale_no' => ReferenceNumberService::sale(),
                        'cash_session_id' => $activeSession->id,
                        'store_id' => $store->id,
                        'customer_id' => $original?->customer_id,
                        'payment_method_id' => $cashMethod->id,
                        'subtotal' => $replacementValue,
                        'discount_amount' => 0,
                        'total_amount' => $replacementValue,
                        'amount_paid' => $replacementValue,
                        'change_amount' => 0,
                        'notes' => "Tukar dari retur {$return->return_no}",
                        'customer_name' => $original?->customer_name,
                        'customer_phone' => $original?->customer_phone,
                        'payment_status' => 'lunas',
                        'settled_at' => now(),
                        'created_by' => Auth::id(),
                    ]);

                    foreach ((array) $r->replacement_items as $row) {
                        $variant = ProductVariant::with('product')->findOrFail($row['variant_id']);
                        $qty = (int) $row['qty'];
                        $price = (float) $row['unit_price'];

                        $stock = Stock::where('product_variant_id', $variant->id)
                            ->where('location_type', 'store')->where('location_id', $store->id)
                            ->lockForUpdate()->first();
                        if (!$stock || $stock->qty < $qty) {
                            throw new \RuntimeException("Stok barang pengganti tidak cukup untuk SKU {$variant->sku}. Tersedia: " . ($stock?->qty ?? 0));
                        }

                        SaleItem::create([
                            'sale_id' => $replacementSale->id,
                            'product_variant_id' => $variant->id,
                            'qty' => $qty,
                            'unit_price' => $price,
                            'subtotal' => $price * $qty,
                            'reward_store' => ($variant->product->reward_store ?? 500) * $qty,
                            'reward_owner' => ($variant->product->reward_owner ?? 4500) * $qty,
                            'is_ecer' => false,
                        ]);

                        StockService::mutate($variant, 'store', $store->id, -$qty, 'sale',
                            "Tukar barang {$replacementSale->sale_no}", Sale::class, $replacementSale->id);
                    }

                    $return->update(['exchange_sale_id' => $replacementSale->id]);

                    // Loyalty: nota pengganti (lunas) memberi poin ke customer.
                    if ($replacementSale->customer_id) {
                        $cust = \App\Models\Customer::find($replacementSale->customer_id);
                        if ($cust) {
                            \App\Services\LoyaltyService::award($cust, $replacementSale);
                        }
                    }
                }

                // Akuntansi kas: net laci = selisih. Refund transfer tidak menyentuh laci.
                $transferRefundOut = ($refundMethod === 'transfer') ? $refundToCustomer : 0;
                $refundIncrement = $returnedValue - $transferRefundOut;
                if ($refundIncrement > 0) {
                    $activeSession->increment('refund_amount', $refundIncrement);
                }

                AuditLogService::log('create', 'CustomerReturn',
                    ($type === 'exchange' ? "Tukar barang {$return->return_no}" : "Retur {$return->return_no}"),
                    null, null, CustomerReturn::class, $return->id);

                return $return;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('returns.customer.show', $return)->with('success', "Retur {$return->return_no} berhasil diproses.");
    }

    public function show(CustomerReturn $return)
    {
        $this->authorize('view customer return');
        $return->load(['store', 'reason', 'creator', 'processor', 'sale', 'items.variant.product', 'items.variant.color', 'items.variant.size']);
        return view('returns.customer.show', compact('return'));
    }

    public function searchSale(Request $r)
    {
        $this->authorize('process customer return');
        $saleNo = $r->input('sale_no');

        if (!$saleNo) {
            return response()->json(['error' => 'Nomor struk tidak diberikan'], 400);
        }

        $user = Auth::user();
        $store = $user->primaryStore();

        if (!$store) {
            return response()->json(['error' => 'Akun Anda belum ditugaskan ke toko manapun.'], 403);
        }

        $sale = Sale::where('sale_no', $saleNo)
            ->where('store_id', $store->id)
            ->with(['items.variant.product', 'items.variant.color', 'items.variant.size'])
            ->first();

        if (!$sale) {
            return response()->json(['error' => 'Transaksi tidak ditemukan atau tidak berasal dari toko ini'], 404);
        }

        $items = $sale->items->map(function ($item) {
            $v = $item->variant;
            return [
                'id'    => $v->id,
                'sku'   => $v->sku,
                'label' => $v->product->name . ' · ' . $v->color->name . ' / ' . $v->size->name,
                'price' => (float) $item->unit_price,
                'qty'   => $item->qty,
            ];
        });

        return response()->json([
            'sale_id' => $sale->id,
            'sale_no' => $sale->sale_no,
            'date'    => $sale->created_at->format('d/m/Y H:i'),
            'items'   => $items
        ]);
    }

    public function searchSales(Request $r)
    {
        $this->authorize('process customer return');
        $user  = Auth::user();
        $store = $user->primaryStore();

        if (!$store) {
            return response()->json([]);
        }

        $q = $r->input('q', '');

        $sales = Sale::where('store_id', $store->id)
            ->when($q, fn($query) => $query->where('sale_no', 'like', "%{$q}%"))
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get(['id', 'sale_no', 'created_at', 'total_amount']);

        return response()->json($sales->map(fn($s) => [
            'id'     => $s->id,
            'sale_no'=> $s->sale_no,
            'date'   => $s->created_at->format('d/m/Y H:i'),
            'total'  => $s->total_amount,
        ]));
    }
}
