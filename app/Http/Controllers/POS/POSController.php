<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Services\AuditLogService;
use App\Services\CreditService;
use App\Services\CustomerService;
use App\Services\ReferenceNumberService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class POSController extends Controller
{
    public function index()
    {
        $this->authorize('access pos');
        $user = \Illuminate\Support\Facades\Auth::user();
        $session = \App\Models\CashSession::where('user_id', $user->id)->where('status', 'open')->first();

        if (!$session) {
            return redirect()->route('pos.session.index')->with('warning', 'Buka sesi kasir terlebih dahulu.');
        }

        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        $store = $session->store;

        // TAMBAHKAN 'product.images' dan 'image' PADA QUERY DI BAWAH
        $catalog = \App\Models\ProductVariant::with(['product.brand', 'color', 'size', 'product.images', 'image'])
            ->where('is_active', true)
            ->whereHas('product', fn($q) => $q->where('is_active', true))
            ->get()
            ->map(function ($v) use ($store) {
                $stockRecord = \App\Models\Stock::where('product_variant_id', $v->id)
                    ->where('location_type', 'store')
                    ->where('location_id', $store->id)
                    ->first();
                
                $stock = $stockRecord ? $stockRecord->qty : 0;

                // AMBIL DATA GAMBAR (Gambar varian, gambar utama, atau gambar pertama)
                $image = $v->image ?? $v->product->images->where('is_primary', true)->first() ?? $v->product->images->first();
                $imageUrl = $image ? asset('storage/' . $image->path) : 'https://via.placeholder.com/300x300.png?text=No+Image';

                return [
                    'id'              => $v->id,
                    'sku'             => $v->sku,
                    'name'            => $v->product->name . ' · ' . $v->color->name . ' / ' . $v->size->name,
                    'price'           => $v->sellPrice(),
                    'price_formatted' => 'Rp ' . number_format($v->sellPrice(), 0, ',', '.'),
                    'grosir_price'    => (float) $v->product->base_price,
                    'retail_price'    => (float) ($v->product->retail_price ?? ($v->sellPrice() + 20000)),
                    'stock'           => $stock,
                    'image'           => $imageUrl,
                ];
            });

        return view('pos.index', compact('session', 'store', 'paymentMethods', 'catalog'));
    }

    public function exportReport(Request $r)
    {
        $this->authorize('view pos');
        $user = Auth::user();
        $store = $user->primaryStore(); // Laporan dibatasi per toko kasir

        if (!$store) {
            return back()->with('error', 'Laporan POS hanya tersedia untuk akun yang ditugaskan ke sebuah toko.');
        }

        $period = $r->input('period', 'today');
        $format = $r->input('format', 'pdf');

        $query = \App\Models\Sale::with(['paymentMethod', 'items'])
            ->where('store_id', $store->id);

        if ($period == 'today') {
            $dateFrom = now()->startOfDay();
            $dateTo = now()->endOfDay();
            $query->whereDate('created_at', now());
            $title = "Harian (" . now()->format('d/m/Y') . ")";
        } elseif ($period == 'weekly') {
            $dateFrom = now()->startOfWeek();
            $dateTo = now()->endOfWeek();
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            $title = "Mingguan (" . $dateFrom->format('d/m') . " - " . $dateTo->format('d/m') . ")";
        } elseif ($period == 'monthly') {
            $dateFrom = now()->startOfMonth();
            $dateTo = now()->endOfMonth();
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
            $title = "Bulanan (" . now()->format('F Y') . ")";
        } else {
            $dateFrom = now()->startOfDay();
            $dateTo = now()->endOfDay();
            $query->whereDate('created_at', now());
            $title = "Harian (" . now()->format('d/m/Y') . ")";
        }

        if (in_array($format, ['excel', 'csv'])) {
            $export = new \App\Exports\SalesExport($store->id, $dateFrom->format('Y-m-d H:i:s'), $dateTo->format('Y-m-d H:i:s'));
            $filename = "Laporan_Penjualan_{$period}_" . now()->format('Ymd');
            if ($format == 'excel') {
                return \Maatwebsite\Excel\Facades\Excel::download($export, $filename . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
            } else {
                return \Maatwebsite\Excel\Facades\Excel::download($export, $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
            }
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        $summary = [
            'total_revenue' => $sales->sum('total_amount'),
            'total_items' => $sales->sum(fn($s) => $s->items->sum('qty')),
            'count' => $sales->count()
        ];

        $pdf = Pdf::loadView('pos.reports.sales_pdf', compact('sales', 'store', 'title', 'summary'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Laporan_Penjualan_{$period}_" . now()->format('Ymd') . ".pdf");
    }

    public function processSale(Request $r)
    {
        $this->authorize('process sale');
        $user = Auth::user();
        $session = CashSession::where('user_id', $user->id)->where('status', 'open')->firstOrFail();

        $r->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount_paid'       => 'required|numeric|min:0',
            'discount_amount'   => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string|max:300',
            'customer_name'     => 'nullable|string|max:150',
            'customer_phone'    => 'nullable|string|max:30',
            'payment_status'    => 'nullable|in:lunas,tempo,dp,po',
            'due_date'          => 'nullable|date',
            'dp_amount'         => 'nullable|numeric|min:0',
            'payments'                      => 'nullable|array',
            'payments.*.payment_method_id'  => 'required_with:payments|exists:payment_methods,id',
            'payments.*.amount'             => 'required_with:payments|numeric|min:0',
            'items'             => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.qty'       => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.is_ecer'   => 'nullable|boolean',
        ]);

        $discountAmount = (float) ($r->discount_amount ?? 0);
        if ($discountAmount > 0) {
            $this->authorize('apply discount');
        }

        try {
            $result = DB::transaction(function () use ($r, $session, $discountAmount) {
                $subtotal = 0;
                $itemsData = [];

                foreach ($r->items as $row) {
                    $variant = ProductVariant::with('product')->findOrFail($row['variant_id']);
                    $qty = (int) $row['qty'];
                    $unitPrice = (float) $row['unit_price'];
                    $lineTotal = $unitPrice * $qty;

                    $stock = Stock::where('product_variant_id', $variant->id)
                        ->where('location_type', 'store')
                        ->where('location_id', $session->store_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$stock || $stock->qty < $qty) {
                        throw new \RuntimeException("Stok tidak cukup untuk SKU {$variant->sku}. Tersedia: " . ($stock?->qty ?? 0));
                    }

                    $subtotal += $lineTotal;
                    $isEcer = filter_var($row['is_ecer'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $itemsData[] = compact('variant', 'qty', 'unitPrice', 'lineTotal', 'isEcer');
                }

                $totalAmount = max(0, $subtotal - $discountAmount);

                // Multi-payment: jika ada array `payments`, pakai itu (boleh >1 metode dalam 1 nota).
                // Jika tidak, jatuh ke metode tunggal seperti perilaku lama.
                $splitPayments = [];
                foreach ((array) $r->input('payments', []) as $p) {
                    $amt = (float) ($p['amount'] ?? 0);
                    if ($amt > 0) {
                        $splitPayments[] = ['payment_method_id' => (int) $p['payment_method_id'], 'amount' => $amt];
                    }
                }

                if (count($splitPayments) > 0) {
                    $amountPaid      = array_sum(array_column($splitPayments, 'amount'));
                    $primaryMethodId = $splitPayments[0]['payment_method_id'];
                } else {
                    $amountPaid      = (float) $r->amount_paid;
                    $primaryMethodId = (int) $r->payment_method_id;
                    if ($amountPaid > 0) {
                        $splitPayments[] = ['payment_method_id' => $primaryMethodId, 'amount' => $amountPaid];
                    }
                }

                $paymentStatus = $r->input('payment_status', 'lunas');
                $dpAmount    = (float) ($r->dp_amount ?? 0);
                $changeAmount = max(0, $amountPaid - $totalAmount);

                if ($paymentStatus === 'lunas' && $amountPaid < $totalAmount) {
                    throw new \RuntimeException("Jumlah pembayaran kurang dari total transaksi.");
                }

                // Utang baru yang timbul dari transaksi ini (0 jika lunas).
                $newDebt = max(0, $totalAmount - $amountPaid);

                // Identifikasi customer (find-or-create) untuk loyalty + kredit.
                $customer = CustomerService::resolveFromSale($r->customer_name, $r->customer_phone);

                // Transaksi kredit wajib teridentifikasi customer-nya (utang melekat ke customer).
                if ($newDebt > 0 && ! $customer) {
                    throw new \RuntimeException("Transaksi kredit wajib mengisi nama/telepon pelanggan.");
                }

                // Enforcement batas kredit GLOBAL (warning/block/approval).
                $credit = CreditService::evaluate($customer, $newDebt);
                $approvalStatus = null;
                if (! $credit->allowed) {
                    if ($credit->requires_approval) {
                        // Mode approval: tahan transaksi. Stok tetap dipotong (reserved),
                        // tapi nota menunggu persetujuan owner sebelum dianggap final.
                        $approvalStatus = 'pending';
                    } else {
                        throw new \RuntimeException($credit->message);
                    }
                }

                $sale = Sale::create([
                    'sale_no'           => ReferenceNumberService::sale(),
                    'cash_session_id'   => $session->id,
                    'store_id'          => $session->store_id,
                    'customer_id'       => $customer?->id,
                    'payment_method_id' => $primaryMethodId,
                    'subtotal'          => $subtotal,
                    'discount_amount'   => $discountAmount,
                    'total_amount'      => $totalAmount,
                    'amount_paid'       => $amountPaid,
                    'change_amount'     => $changeAmount,
                    'notes'             => $r->notes,
                    'customer_name'     => $r->customer_name,
                    'customer_phone'    => $r->customer_phone,
                    'payment_status'    => $paymentStatus,
                    'approval_status'   => $approvalStatus,
                    'due_date'          => $r->due_date,
                    'dp_amount'         => $dpAmount,
                    // Cash-basis: nota lunas langsung settled hari ini (basis pengakuan komisi & settlement).
                    'settled_at'        => $paymentStatus === 'lunas' ? now() : null,
                    'created_by'        => Auth::id(),
                ]);

                foreach ($itemsData as $item) {
                    $baseRewardOwner = (float) ($item['variant']->product->reward_owner ?? 4500);
                    
                    if ($item['isEcer']) {
                        $marginEcer = max(0, $item['unitPrice'] - $item['variant']->sellPrice());
                        $baseRewardOwner += $marginEcer;
                    }

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_variant_id' => $item['variant']->id,
                        'qty' => $item['qty'],
                        'unit_price' => $item['unitPrice'],
                        'subtotal' => $item['lineTotal'],
                        'reward_store' => ($item['variant']->product->reward_store ?? 500) * $item['qty'],
                        'reward_owner' => $baseRewardOwner * $item['qty'],
                        'is_ecer' => $item['isEcer'],
                    ]);

                    StockService::mutate(
                        $item['variant'],
                        'store',
                        $session->store_id,
                        -$item['qty'],
                        'sale',
                        "Penjualan {$sale->sale_no}",
                        Sale::class,
                        $sale->id
                    );
                }

                // Catat tiap pembayaran (single atau split) ke ledger sale_payments.
                foreach ($splitPayments as $sp) {
                    \App\Models\SalePayment::create([
                        'sale_id'           => $sale->id,
                        'amount'            => $sp['amount'],
                        'payment_method_id' => $sp['payment_method_id'],
                        'paid_at'           => now(),
                        'received_by'       => Auth::id(),
                        'note'              => 'Pembayaran saat transaksi',
                    ]);
                }

                // Loyalty: beri poin saat nota lunas (cash-basis).
                if ($paymentStatus === 'lunas' && $customer) {
                    \App\Services\LoyaltyService::award($customer, $sale);
                }

                AuditLogService::log('create', 'Sale', "Transaksi {$sale->sale_no} Rp " . number_format($totalAmount, 0, ',', '.'), null, null, Sale::class, $sale->id);

                // Pesan ke kasir: prioritaskan info pending approval, lalu peringatan kredit.
                if ($approvalStatus === 'pending') {
                    $warning = "Transaksi {$sale->sale_no} MENUNGGU PERSETUJUAN owner (melebihi batas kredit). Barang ditahan sampai disetujui.";
                } else {
                    $warning = ($credit->over_limit && $credit->allowed) ? $credit->message : null;
                }

                return ['sale' => $sale, 'warning' => $warning];
            });

            $sale    = $result['sale'];
            $warning = $result['warning'] ?? null;

            // --- PERUBAHAN BARU: Cek jika request dari AJAX (Pop-up) ---
            if ($r->ajax() || $r->wantsJson()) {
                $sale->load(['store', 'paymentMethod', 'creator', 'items.variant.product']);
                $receiptHtml = view('pos.partials.receipt_html', compact('sale'))->render();

                return response()->json([
                    'success' => true,
                    'sale' => $sale,
                    'warning' => $warning,
                    'html' => $receiptHtml
                ]);
            }
            $redirect = redirect()->route('pos.receipt', $sale)->with('success', "Transaksi {$sale->sale_no} berhasil.");
            return $warning ? $redirect->with('warning', $warning) : $redirect;
        } catch (\RuntimeException $e) {
            if ($r->ajax() || $r->wantsJson())
                return response()->json(['success' => false, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function receipt(Sale $sale)
    {
        $this->authorize('access pos');
        $sale->load(['store', 'paymentMethod', 'creator', 'items.variant.product', 'items.variant.color', 'items.variant.size']);

        if (request()->ajax() || request()->wantsJson()) {
            $receiptHtml = view('pos.partials.receipt_html', compact('sale'))->render();
            return response()->json([
                'success' => true,
                'sale' => $sale,
                'html' => $receiptHtml
            ]);
        }

        return view('pos.receipt', compact('sale'));
    }

    public function history(Request $r)
    {
        $this->authorize('view pos');
        $user = Auth::user();
        $stores = collect();

        if ($user->hasAnyRole(['superadmin', 'owner', 'finance', 'admin gudang'])) {
            $stores = \App\Models\Store::orderBy('name')->get();
        }

        $q = Sale::with(['store', 'paymentMethod', 'creator', 'items'])
            ->when($r->store_id, fn($q) => $q->where('store_id', $r->store_id))
            ->when($r->date_from, fn($q) => $q->whereDate('created_at', '>=', $r->date_from))
            ->when($r->date_to, fn($q) => $q->whereDate('created_at', '<=', $r->date_to))
            ->when($r->sku, function($q) use ($r) {
                $q->whereHas('items.variant', function($sq) use ($r) {
                    $sq->where('sku', 'like', "%{$r->sku}%");
                });
            })
            ->orderBy('created_at', 'desc');

        if (!$user->hasAnyRole(['superadmin', 'owner', 'finance', 'admin gudang'])) {
            $storeIds = $user->stores->pluck('id');
            $q->whereIn('store_id', $storeIds);
        }

        $sales = $q->paginate(25)->withQueryString();
        $variants = \App\Models\ProductVariant::with('product')->where('is_active', true)->orderBy('sku')->get();

        return view('pos.history', compact('sales', 'stores', 'variants'));
    }

    public function searchProduct(Request $r)
    {
        $storeId = (int) $r->store_id;
        $term = trim($r->q ?? '');

        if (strlen($term) < 1 || !$storeId) {
            return response()->json([]);
        }

        $variants = ProductVariant::with(['product.brand', 'color', 'size', 'product.images', 'image'])
            ->where('is_active', true)
            ->whereHas('product', fn($q) => $q->where('is_active', true))
            ->where(
                fn($q) => $q
                    ->where('sku', 'like', "%{$term}%")
                    ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$term}%"))
            )
            ->limit(12)
            ->get()
            ->map(function ($v) use ($storeId) {
                $stockRecord = Stock::where('product_variant_id', $v->id)
                    ->where('location_type', 'store')
                    ->where('location_id', $storeId)
                    ->first();
                
                $stock = $stockRecord ? $stockRecord->qty : 0;

                $image = $v->image ?? $v->product->images->where('is_primary', true)->first() ?? $v->product->images->first();
                $imageUrl = $image ? asset('storage/' . $image->path) : 'https://via.placeholder.com/300x300.png?text=No+Image';

                return [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'name' => $v->product->name . ' · ' . $v->color->name . ' / ' . $v->size->name,
                    'price' => $v->sellPrice(),
                    'price_formatted' => 'Rp ' . number_format($v->sellPrice(), 0, ',', '.'),
                    'grosir_price' => (float) $v->product->base_price,
                    'retail_price' => (float) ($v->product->retail_price ?? ($v->sellPrice() + 20000)),
                    'stock' => $stock,
                    'image' => $imageUrl,
                ];
            });

        return response()->json($variants);
    }

    /**
     * Autocomplete pelanggan berdasarkan nama atau telepon dari riwayat transaksi.
     */
    public function autocompleteCustomer(Request $r)
    {
        $term = trim($r->q ?? '');
        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $results = \App\Models\Customer::where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn ($c) => [
                'name'  => $c->name,
                'phone' => $c->phone ?? '',
            ]);

        return response()->json($results);
    }
}
