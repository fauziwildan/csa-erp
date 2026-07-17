<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OnlineOrder;
use App\Models\StockLedger;
use App\Services\AuditLogService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Pesanan online dari landing page katalog.
 * Alur: pending → (superadmin konfirmasi bayar) → paid + stok gudang dipotong.
 */
class OnlineOrderController extends Controller
{
    public function index(Request $r)
    {
        $this->authorize('view online order');

        $q = OnlineOrder::with(['warehouse', 'payer'])
            ->when($r->status, fn($q) => $q->where('status', $r->status))
            ->when($r->search, function ($q) use ($r) {
                $term = $r->search;
                $q->where(fn($s) => $s->where('order_no', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%"));
            })
            ->when($r->date_from, fn($q) => $q->whereDate('created_at', '>=', $r->date_from))
            ->when($r->date_to, fn($q) => $q->whereDate('created_at', '<=', $r->date_to))
            ->latest();

        $orders = $q->paginate(25)->withQueryString();

        // Ringkasan cashflow & stockflow (mengikuti filter tanggal)
        $base = fn() => OnlineOrder::query()
            ->when($r->date_from, fn($q) => $q->whereDate('created_at', '>=', $r->date_from))
            ->when($r->date_to, fn($q) => $q->whereDate('created_at', '<=', $r->date_to));

        $summary = [
            'pending_count' => (clone $base())->where('status', 'pending')->count(),
            'pending_value' => (clone $base())->where('status', 'pending')->sum('total_amount'),
            'paid_count'    => (clone $base())->where('status', 'paid')->count(),
            'paid_value'    => (clone $base())->where('status', 'paid')->sum('total_amount'),
            'paid_qty'      => (clone $base())->where('status', 'paid')->sum('total_qty'),
            'cancelled'     => (clone $base())->where('status', 'cancelled')->count(),
        ];

        return view('admin.online-orders.index', compact('orders', 'summary'));
    }

    public function show(OnlineOrder $onlineOrder)
    {
        $this->authorize('view online order');
        $onlineOrder->load(['items.variant.product', 'warehouse', 'payer', 'canceller']);

        // Stockflow: pergerakan stok yang dihasilkan pesanan ini
        $ledgers = StockLedger::with('variant.product')
            ->where('reference_type', OnlineOrder::class)
            ->where('reference_id', $onlineOrder->id)
            ->get();

        return view('admin.online-orders.show', compact('onlineOrder', 'ledgers'));
    }

    /**
     * Konfirmasi pembayaran → potong stok gudang + tandai lunas.
     */
    public function pay(Request $r, OnlineOrder $onlineOrder)
    {
        $this->authorize('manage online order');

        if (! $onlineOrder->isPending()) {
            return back()->with('error', 'Pesanan ini tidak dalam status menunggu pembayaran.');
        }

        try {
            DB::transaction(function () use ($onlineOrder) {
                $onlineOrder->load('items.variant');

                foreach ($onlineOrder->items as $item) {
                    // StockService menolak bila stok tidak cukup (RuntimeException)
                    StockService::mutate(
                        $item->variant,
                        'warehouse',
                        $onlineOrder->warehouse_id,
                        -$item->qty,
                        'sale',
                        "Pesanan online {$onlineOrder->order_no}",
                        OnlineOrder::class,
                        $onlineOrder->id
                    );
                }

                $onlineOrder->update([
                    'status'  => 'paid',
                    'paid_at' => now(),
                    'paid_by' => Auth::id(),
                ]);

                AuditLogService::log(
                    'update', 'OnlineOrder',
                    "Konfirmasi pembayaran {$onlineOrder->order_no} — stok gudang dipotong",
                    null, null, OnlineOrder::class, $onlineOrder->id
                );
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }

        return back()->with('success', "Pesanan {$onlineOrder->order_no} lunas. Stok gudang sudah dipotong.");
    }

    public function cancel(Request $r, OnlineOrder $onlineOrder)
    {
        $this->authorize('manage online order');

        if ($onlineOrder->isPaid()) {
            return back()->with('error', 'Pesanan yang sudah lunas tidak dapat dibatalkan.');
        }
        if ($onlineOrder->isCancelled()) {
            return back()->with('error', 'Pesanan sudah dibatalkan.');
        }

        $data = $r->validate(['cancel_reason' => 'required|string|max:255']);

        $onlineOrder->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancelled_by'  => Auth::id(),
            'cancel_reason' => $data['cancel_reason'],
        ]);

        AuditLogService::log('update', 'OnlineOrder', "Batalkan {$onlineOrder->order_no}",
            null, null, OnlineOrder::class, $onlineOrder->id);

        return back()->with('success', 'Pesanan dibatalkan.');
    }

    /**
     * Resi pengiriman ukuran A5 (siap print).
     */
    public function resi(OnlineOrder $onlineOrder)
    {
        $this->authorize('view online order');
        $onlineOrder->load(['items', 'warehouse']);

        return view('admin.online-orders.resi', compact('onlineOrder'));
    }
}
