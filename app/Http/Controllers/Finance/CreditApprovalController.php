<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Services\AuditLogService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreditApprovalController extends Controller
{
    /** Batasi ke toko yang boleh diakses user (kepala toko = toko sendiri). */
    private function scopeStores($query)
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['owner', 'superadmin'])) {
            $query->whereIn('store_id', $user->stores->pluck('id'));
        }
        return $query;
    }

    public function index()
    {
        $this->authorize('approve credit');

        $sales = $this->scopeStores(
            Sale::with(['store', 'customer', 'creator', 'items'])
                ->where('approval_status', 'pending')
        )->orderBy('created_at')->paginate(20);

        return view('finance.credit_approvals', compact('sales'));
    }

    public function approve(Sale $sale)
    {
        $this->authorize('approve credit');
        $this->ensureAccess($sale);

        if ($sale->approval_status !== 'pending') {
            return back()->with('error', 'Transaksi ini sudah diproses.');
        }

        $sale->update([
            'approval_status' => 'approved',
            'approved_by'     => Auth::id(),
            'approved_at'     => now(),
        ]);

        AuditLogService::log('approve', 'Sale', "Kredit {$sale->sale_no} disetujui", null, null, Sale::class, $sale->id);

        return back()->with('success', "Transaksi {$sale->sale_no} disetujui.");
    }

    public function reject(Request $request, Sale $sale)
    {
        $this->authorize('approve credit');
        $this->ensureAccess($sale);

        $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        if ($sale->approval_status !== 'pending') {
            return back()->with('error', 'Transaksi ini sudah diproses.');
        }

        DB::transaction(function () use ($sale, $request) {
            // Kembalikan stok yang sempat ditahan (reserved) saat transaksi dibuat.
            foreach ($sale->items as $item) {
                $variant = ProductVariant::find($item->product_variant_id);
                if ($variant) {
                    StockService::mutate(
                        $variant,
                        'store',
                        $sale->store_id,
                        $item->qty,
                        'return',
                        "Pembatalan kredit {$sale->sale_no} (ditolak)",
                        Sale::class,
                        $sale->id
                    );
                }
            }

            $reason = $request->reason ?: '-';
            AuditLogService::log('reject', 'Sale', "Kredit {$sale->sale_no} DITOLAK. Alasan: {$reason}", null, null, Sale::class, $sale->id);

            // Nota pending yang ditolak dihapus (tidak pernah menjadi transaksi final).
            $sale->items()->delete();
            $sale->delete();
        });

        return back()->with('success', 'Transaksi kredit ditolak & stok dikembalikan.');
    }

    /** Pastikan kepala toko hanya bisa memproses toko sendiri. */
    private function ensureAccess(Sale $sale): void
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['owner', 'superadmin']) && ! $user->stores->pluck('id')->contains($sale->store_id)) {
            abort(403, 'Anda tidak berhak memproses transaksi toko ini.');
        }
    }
}
