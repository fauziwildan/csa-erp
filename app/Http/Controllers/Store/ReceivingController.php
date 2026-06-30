<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Store;
use App\Services\AuditLogService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReceivingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('receive store shipment');

        $user  = Auth::user();
        $query = Shipment::with(['warehouse', 'store', 'creator'])
            ->whereIn('status', ['shipped', 'arrived', 'received']);

        // Non-superadmin only sees their own stores
        if (! $user->hasRole('superadmin') && ! $user->hasRole('owner')) {
            $storeIds = $user->stores->pluck('id');
            $query->whereIn('store_id', $storeIds);
        }

        if ($request->store_id) {
            $query->where('store_id', $request->store_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $shipments = $query->latest()->paginate(30)->withQueryString();
        $stores    = Store::where('is_active', true)->orderBy('name')->get();

        return view('store.receiving.index', compact('shipments', 'stores'));
    }

    public function show(Shipment $shipment): View
    {
        $this->authorize('receive store shipment');
        $shipment->load(['warehouse', 'store', 'creator',
            'items.variant.product.brand', 'items.variant.color', 'items.variant.size']);

        return view('store.receiving.show', compact('shipment'));
    }

    public function confirm(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('receive store shipment');

        if (! in_array($shipment->status, ['shipped', 'arrived'])) {
            return back()->with('error', 'Pengiriman ini tidak dalam status yang bisa diterima.');
        }

        $data = $request->validate([
            'items'                 => 'required|array',
            'items.*.id'            => 'required|exists:shipment_items,id',
            'items.*.qty_received'  => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($shipment, $data) {
            $shipment->load('items.variant');

            foreach ($data['items'] as $row) {
                $item = $shipment->items->find($row['id']);
                if (! $item) continue;

                $qtyReceived = min((int) $row['qty_received'], $item->qty_sent);
                $item->update(['qty_received' => $qtyReceived]);

                if ($qtyReceived > 0) {
                    StockService::mutate(
                        $item->variant,
                        'store',
                        $shipment->store_id,
                        $qtyReceived,
                        'transfer_in',
                        "Penerimaan pengiriman {$shipment->shipment_no}",
                        Shipment::class,
                        $shipment->id
                    );
                }

                // Selisih yang tidak diterima toko dikembalikan ke stok gudang asal
                // (stok sudah dikurangi penuh sebesar qty_sent saat status 'shipped').
                $shortfall = $item->qty_sent - $qtyReceived;
                if ($shortfall > 0) {
                    StockService::mutate(
                        $item->variant,
                        'warehouse',
                        $shipment->warehouse_id,
                        $shortfall,
                        'transfer_in',
                        "Selisih pengiriman {$shipment->shipment_no} dikembalikan ke gudang",
                        Shipment::class,
                        $shipment->id
                    );
                }
            }

            $shipment->update([
                'status'      => 'received',
                'received_at' => now(),
                'received_by' => Auth::id(),
            ]);

            AuditLogService::log('receive', 'shipments', "Pengiriman {$shipment->shipment_no} diterima oleh toko {$shipment->store->name}",
                null, null, Shipment::class, $shipment->id);
        });

        return redirect()->route('store.receiving.index')
            ->with('success', "Pengiriman {$shipment->shipment_no} berhasil diterima.");
    }
}
