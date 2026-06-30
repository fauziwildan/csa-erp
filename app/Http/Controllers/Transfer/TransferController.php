<?php

namespace App\Http\Controllers\Transfer;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Services\AuditLogService;
use App\Services\ReferenceNumberService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function index(Request $r)
    {
        $this->authorize('view transfer');
        $user   = Auth::user();
        $stores = Store::orderBy('name')->get();

        // Tidak lagi auto-kunci ke toko sendiri — biar transfer MASUK (toko tujuan = toko kita) ikut tampil.
        // Pembatasan ke toko user tetap ditangani oleh scope di bawah (from ATAU to ∈ toko user).
        $fromStoreId = $r->get('from_store_id');

        $q = Transfer::with(['fromStore', 'toStore', 'items'])
            ->when($fromStoreId, fn($q) => $q->where('from_store_id', $fromStoreId))
            ->when($r->to_store_id,   fn($q) => $q->where('to_store_id', $r->to_store_id))
            ->when($r->status,        fn($q) => $q->where('status', $r->status))
            ->orderBy('created_at', 'desc');

        if (!$user->hasAnyRole(['superadmin', 'owner', 'finance', 'admin gudang'])) {
            $storeIds = $user->stores->pluck('id');
            $q->where(function ($q) use ($storeIds) {
                $q->whereIn('from_store_id', $storeIds)
                  ->orWhereIn('to_store_id', $storeIds);
            });
        }

        $transfers = $q->paginate(20)->withQueryString();
        $transfers->appends(['from_store_id' => $fromStoreId]);

        return view('transfers.index', compact('transfers', 'stores', 'fromStoreId'));
    }

    public function create()
    {
        $this->authorize('request store transfer');
        $user  = Auth::user();
        $stores = Store::where('is_active', true)->orderBy('name')->get();

        $variants = []; // No longer eager loading variants to prevent memory issues

        return view('transfers.create', compact('stores', 'variants'));
    }

    public function store(Request $r)
    {
        $this->authorize('request store transfer');

        $r->validate([
            'from_store_id'          => 'required|exists:stores,id',
            'to_store_id'            => 'required|exists:stores,id|different:from_store_id',
            'notes'                  => 'nullable|string|max:500',
            'items'                  => 'required|array|min:1',
            'items.*.variant_id'     => 'required|exists:product_variants,id',
            'items.*.qty_requested'  => 'required|integer|min:1',
        ]);

        $transfer = DB::transaction(function () use ($r) {
            $transfer = Transfer::create([
                'transfer_no'   => ReferenceNumberService::transfer(),
                'from_store_id' => $r->from_store_id,
                'to_store_id'   => $r->to_store_id,
                'notes'         => $r->notes,
                'status'        => 'pending',
                'created_by'    => Auth::id(),
            ]);

            foreach ($r->items as $item) {
                TransferItem::create([
                    'transfer_id'        => $transfer->id,
                    'product_variant_id' => $item['variant_id'],
                    'qty_requested'      => $item['qty_requested'],
                ]);
            }

            AuditLogService::log('create', 'Transfer', "Buat transfer {$transfer->transfer_no}", null, $transfer->toArray(), Transfer::class, $transfer->id);

            return $transfer;
        });

        return redirect()->route('transfers.show', $transfer)->with('success', "Transfer {$transfer->transfer_no} berhasil dibuat.");
    }

    public function show(Transfer $transfer)
    {
        $this->authorize('view transfer');
        $transfer->load([
            'fromStore', 'toStore', 'creator', 'approver', 'rejecter', 'shipper', 'receiver',
            'items.variant.product.brand', 'items.variant.color', 'items.variant.size',
        ]);

        return view('transfers.show', compact('transfer'));
    }

    public function approve(Request $r, Transfer $transfer)
    {
        $this->authorize('approve store transfer');

        if (!$transfer->isPending()) {
            return back()->with('error', 'Transfer tidak dapat disetujui pada status ini.');
        }

        $transfer->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        AuditLogService::log('approve', 'Transfer', "Setujui {$transfer->transfer_no}", null, null, Transfer::class, $transfer->id);

        return redirect()->route('transfers.show', $transfer)->with('success', 'Transfer disetujui. Toko asal dapat memproses pengiriman.');
    }

    public function reject(Request $r, Transfer $transfer)
    {
        $this->authorize('approve store transfer');

        $r->validate(['rejection_reason' => 'required|string|max:500']);

        if (!$transfer->isPending()) {
            return back()->with('error', 'Transfer tidak dapat ditolak pada status ini.');
        }

        $transfer->update([
            'status'           => 'rejected',
            'rejection_reason' => $r->rejection_reason,
            'rejected_at'      => now(),
            'rejected_by'      => Auth::id(),
        ]);

        AuditLogService::log('reject', 'Transfer', "Tolak {$transfer->transfer_no}", null, null, Transfer::class, $transfer->id);

        return redirect()->route('transfers.show', $transfer)->with('success', 'Transfer ditolak.');
    }

    public function ship(Request $r, Transfer $transfer)
    {
        $this->authorize('request store transfer');

        // Pengiriman hanya boleh dilakukan oleh pihak toko asal (kecuali admin global).
        if (! $transfer->userCanShip(Auth::user())) {
            abort(403, 'Hanya toko asal yang dapat mengirim transfer ini.');
        }

        if (!$transfer->isApproved()) {
            return back()->with('error', 'Transfer harus disetujui terlebih dahulu.');
        }

        $r->validate([
            'items'              => 'required|array',
            'items.*.id'         => 'required|exists:transfer_items,id',
            'items.*.qty_sent'   => 'required|integer|min:0',
        ]);

        try {
            DB::transaction(function () use ($r, $transfer) {
                $transfer->load('fromStore', 'toStore');

                foreach ($r->items as $row) {
                    $item    = TransferItem::with('variant')->findOrFail($row['id']);
                    $qtySent = (int) $row['qty_sent'];

                    if ($qtySent > $item->qty_requested) {
                        throw new \RuntimeException("Qty kirim melebihi qty diminta untuk SKU {$item->variant->sku}.");
                    }

                    if ($qtySent > 0) {
                        StockService::mutate(
                            $item->variant,
                            'store',
                            $transfer->from_store_id,
                            -$qtySent,
                            'transfer_out',
                            "Transfer {$transfer->transfer_no} → {$transfer->toStore->name}",
                            Transfer::class,
                            $transfer->id
                        );
                    }

                    $item->update(['qty_sent' => $qtySent]);
                }

                $transfer->update([
                    'status'     => 'shipped',
                    'shipped_at' => now(),
                    'shipped_by' => Auth::id(),
                ]);
            });

            AuditLogService::log('ship', 'Transfer', "Kirim {$transfer->transfer_no}", null, null, Transfer::class, $transfer->id);
            return redirect()->route('transfers.show', $transfer)->with('success', 'Transfer dikirim. Stok toko asal sudah dikurangi.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function receive(Request $r, Transfer $transfer)
    {
        $this->authorize('receive transfer');

        // Penerimaan hanya boleh oleh toko TUJUAN (atau admin global), bukan pengirim.
        if (! $transfer->userCanReceive(Auth::user())) {
            abort(403, 'Hanya toko tujuan yang dapat menerima transfer ini.');
        }

        if (!$transfer->isShipped()) {
            return back()->with('error', 'Transfer belum dikirim.');
        }

        $r->validate([
            'items'                  => 'required|array',
            'items.*.id'             => 'required|exists:transfer_items,id',
            'items.*.qty_received'   => 'required|integer|min:0',
        ]);

        try {
            DB::transaction(function () use ($r, $transfer) {
                $transfer->load('fromStore', 'toStore');

                foreach ($r->items as $row) {
                    $item        = TransferItem::with('variant')->findOrFail($row['id']);
                    $qtyReceived = (int) $row['qty_received'];

                    if ($qtyReceived > $item->qty_sent) {
                        throw new \RuntimeException("Qty terima melebihi qty kirim untuk SKU {$item->variant->sku}.");
                    }

                    if ($qtyReceived > 0) {
                        StockService::mutate(
                            $item->variant,
                            'store',
                            $transfer->to_store_id,
                            $qtyReceived,
                            'transfer_in',
                            "Transfer {$transfer->transfer_no} ← {$transfer->fromStore->name}",
                            Transfer::class,
                            $transfer->id
                        );
                    }

                    // Selisih yang tidak diterima dikembalikan ke stok toko asal
                    // (stok toko asal sudah dikurangi penuh sebesar qty_sent saat 'shipped').
                    $shortfall = $item->qty_sent - $qtyReceived;
                    if ($shortfall > 0) {
                        StockService::mutate(
                            $item->variant,
                            'store',
                            $transfer->from_store_id,
                            $shortfall,
                            'transfer_in',
                            "Selisih transfer {$transfer->transfer_no} dikembalikan ke {$transfer->fromStore->name}",
                            Transfer::class,
                            $transfer->id
                        );
                    }

                    $item->update(['qty_received' => $qtyReceived]);
                }

                $transfer->update([
                    'status'      => 'received',
                    'received_at' => now(),
                    'received_by' => Auth::id(),
                ]);
            });

            AuditLogService::log('receive', 'Transfer', "Terima {$transfer->transfer_no}", null, null, Transfer::class, $transfer->id);
            return redirect()->route('transfers.show', $transfer)->with('success', 'Transfer diterima. Stok toko tujuan sudah ditambahkan.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function printDoc(Transfer $transfer)
    {
        $this->authorize('view transfer');
        $transfer->load([
            'fromStore', 'toStore', 'creator',
            'items.variant.product', 'items.variant.color', 'items.variant.size',
        ]);

        return view('transfers.print', compact('transfer'));
    }
}
