<?php

namespace App\Http\Controllers\Returns;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\ReturnReason;
use App\Models\Store;
use App\Models\StoreReturn;
use App\Models\StoreReturnItem;
use App\Models\Warehouse;
use App\Services\AuditLogService;
use App\Services\ReferenceNumberService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreReturnController extends Controller
{
    public function index(Request $r)
    {
        $this->authorize('view store return');
        $user       = Auth::user();
        $stores     = $user->hasAnyRole(['superadmin', 'owner', 'finance', 'admin gudang']) ? Store::orderBy('name')->get() : collect();
        $warehouses = Warehouse::orderBy('name')->get();

        $q = StoreReturn::with(['store', 'warehouse', 'reason'])
            ->when($r->store_id,     fn($q) => $q->where('store_id', $r->store_id))
            ->when($r->warehouse_id, fn($q) => $q->where('warehouse_id', $r->warehouse_id))
            ->when($r->status,       fn($q) => $q->where('status', $r->status))
            ->orderBy('created_at', 'desc');

        if (!$user->hasAnyRole(['superadmin', 'owner', 'finance', 'admin gudang'])) {
            $storeIds = $user->stores->pluck('id');
            $q->whereIn('store_id', $storeIds);
        }

        $returns = $q->paginate(20)->withQueryString();
        return view('returns.store.index', compact('returns', 'stores', 'warehouses'));
    }

    public function create()
    {
        $this->authorize('create store return');
        $user       = Auth::user();
        $store      = $user->primaryStore();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $reasons    = ReturnReason::where('is_active', true)->whereIn('type', ['store', 'both'])->get();
        $variants = []; // No longer eager loading variants to prevent memory issues

        return view('returns.store.create', compact('store', 'warehouses', 'reasons', 'variants'));
    }

    public function store(Request $r)
    {
        $this->authorize('create store return');
        $user  = Auth::user();
        $store = $user->primaryStore();

        if (!$store) {
            return back()->withInput()->with('error', 'Anda belum ditugaskan ke toko manapun. Hubungi administrator.');
        }

        $r->validate([
            'warehouse_id'          => 'required|exists:warehouses,id',
            'return_reason_id'      => 'nullable|exists:return_reasons,id',
            'notes'                 => 'nullable|string|max:500',
            'items'                 => 'required|array|min:1',
            'items.*.variant_id'    => 'required|exists:product_variants,id',
            'items.*.qty_returned'  => 'required|integer|min:1',
        ]);

        try {
            $return = DB::transaction(function () use ($r, $store) {
                $return = StoreReturn::create([
                    'return_no'        => ReferenceNumberService::storeReturn(),
                    'store_id'         => $store->id,
                    'warehouse_id'     => $r->warehouse_id,
                    'return_reason_id' => $r->return_reason_id,
                    'status'           => 'pending',
                    'notes'            => $r->notes,
                    'created_by'       => Auth::id(),
                ]);

                foreach ($r->items as $row) {
                    $variant = ProductVariant::findOrFail($row['variant_id']);
                    $qty     = (int) $row['qty_returned'];

                    StoreReturnItem::create([
                        'store_return_id'    => $return->id,
                        'product_variant_id' => $variant->id,
                        'qty_returned'       => $qty,
                    ]);

                    StockService::mutate(
                        $variant, 'store', $store->id, -$qty, 'transfer_out',
                        "Retur toko {$return->return_no} → gudang",
                        StoreReturn::class, $return->id
                    );
                }

                AuditLogService::log('create', 'StoreReturn', "Buat retur {$return->return_no}", null, null, StoreReturn::class, $return->id);
                return $return;
            });

            return redirect()->route('returns.store.show', $return)->with('success', "Retur {$return->return_no} dibuat. Stok toko sudah dikurangi.");
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(StoreReturn $return)
    {
        $this->authorize('view store return');
        $return->load(['store', 'warehouse', 'reason', 'creator', 'receiver', 'inspector', 'items.variant.product', 'items.variant.color', 'items.variant.size']);
        return view('returns.store.show', compact('return'));
    }

    public function receive(Request $r, StoreReturn $return)
    {
        $this->authorize('receive store return');

        if (Auth::user()->hasRole(['kepala toko', 'kasir']) && !Auth::user()->hasRole('superadmin')) {
            abort(403, 'Aksi ini hanya dapat dilakukan oleh gudang.');
        }

        if (!$return->isPending()) {
            return back()->with('error', 'Retur tidak dalam status menunggu.');
        }

        $return->update([
            'status'      => 'received',
            'received_at' => now(),
            'received_by' => Auth::id(),
        ]);

        AuditLogService::log('receive', 'StoreReturn', "Terima retur {$return->return_no}", null, null, StoreReturn::class, $return->id);
        return back()->with('success', 'Retur diterima di gudang. Lanjutkan dengan inspeksi.');
    }

    public function inspect(Request $r, StoreReturn $return)
    {
        $this->authorize('inspect return');

        if (!$return->isReceived()) {
            return back()->with('error', 'Retur harus diterima terlebih dahulu.');
        }

        $r->validate([
            'inspection_notes'    => 'nullable|string|max:500',
            'items'               => 'required|array',
            'items.*.id'          => 'required|exists:store_return_items,id',
            'items.*.qty_good'    => 'required|integer|min:0',
            'items.*.qty_damaged' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($r, $return) {
            foreach ($r->items as $row) {
                $item       = StoreReturnItem::with('variant')->findOrFail($row['id']);
                $qtyGood    = (int) $row['qty_good'];
                $qtyDamaged = (int) $row['qty_damaged'];
                $item->update(['qty_good' => $qtyGood, 'qty_damaged' => $qtyDamaged]);

                if ($qtyGood > 0) {
                    StockService::mutate(
                        $item->variant, 'warehouse', $return->warehouse_id, $qtyGood, 'transfer_in',
                        "Retur toko {$return->return_no} (baik)",
                        StoreReturn::class, $return->id
                    );
                }
            }

            $return->update([
                'status'           => 'inspected',
                'inspection_notes' => $r->inspection_notes,
                'inspected_at'     => now(),
                'inspected_by'     => Auth::id(),
            ]);
        });

        AuditLogService::log('inspect', 'StoreReturn', "Inspeksi retur {$return->return_no}", null, null, StoreReturn::class, $return->id);
        return back()->with('success', 'Inspeksi selesai. Stok gudang diperbarui.');
    }
}
