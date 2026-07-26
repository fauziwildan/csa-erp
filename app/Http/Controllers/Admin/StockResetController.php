<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\AuditLogService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Menu Reset Stok (khusus superadmin).
 * - Mode "semua"   : set stok SEMUA varian di satu lokasi ke nilai target (default 0).
 * - Mode "sebagian": pilih SKU tertentu, set qty target per SKU.
 * Lokasi = Gudang (warehouse) atau salah satu Toko (store) yang dipilih.
 *
 * Semua perubahan lewat StockService::mutate → tercatat di stock_ledger (audit trail utuh).
 */
class StockResetController extends Controller
{
    public function index()
    {
        $this->authorize('reset stock');

        $warehouses = Warehouse::orderBy('name')->get(['id', 'name']);
        $stores     = Store::orderBy('name')->get(['id', 'name']);

        // Daftar ringan varian untuk picker mode "sebagian".
        $variants = ProductVariant::with(['product:id,name', 'color:id,name', 'size:id,name'])
            ->orderBy('sku')
            ->get()
            ->map(fn ($v) => [
                'id'    => $v->id,
                'sku'   => $v->sku,
                'label' => trim(($v->product->name ?? 'Produk') . ' · ' . ($v->color->name ?? '-') . ' / ' . ($v->size->name ?? '-')),
            ])
            ->values();

        return view('admin.stock-reset.index', compact('warehouses', 'stores', 'variants'));
    }

    public function execute(Request $request)
    {
        $this->authorize('reset stock');

        $data = $request->validate([
            'location_type'      => 'required|in:warehouse,store',
            'location_id'        => 'required|integer',
            'mode'               => 'required|in:all,partial',
            'target_qty'         => 'nullable|integer|min:0',            // mode "all"
            'items'              => 'array',                             // mode "partial"
            'items.*.variant_id' => 'required_with:items|integer|exists:product_variants,id',
            'items.*.qty'        => 'required_with:items|integer|min:0',
            'confirm'            => 'accepted',
        ], [
            'confirm.accepted' => 'Anda harus mencentang konfirmasi terlebih dahulu.',
        ]);

        $locType = $data['location_type'];
        $locId   = (int) $data['location_id'];

        // Pastikan lokasi valid.
        $location = $locType === 'warehouse' ? Warehouse::find($locId) : Store::find($locId);
        if (!$location) {
            return back()->withInput()->with('error', 'Lokasi yang dipilih tidak ditemukan.');
        }

        if ($data['mode'] === 'partial' && empty($data['items'])) {
            return back()->withInput()->with('error', 'Pilih minimal satu SKU untuk mode reset sebagian.');
        }

        $affected = 0;

        DB::transaction(function () use ($data, $locType, $locId, &$affected) {
            if ($data['mode'] === 'all') {
                $target = (int) ($data['target_qty'] ?? 0);

                $stocks = Stock::where('location_type', $locType)
                    ->where('location_id', $locId)
                    ->where('qty', '!=', $target)
                    ->with('variant')
                    ->get();

                foreach ($stocks as $stock) {
                    if (!$stock->variant) {
                        continue; // varian sudah terhapus
                    }
                    $delta = $target - $stock->qty;
                    if ($delta === 0) {
                        continue;
                    }
                    StockService::mutate(
                        $stock->variant, $locType, $locId, $delta,
                        'adjust', 'Reset stok (semua) oleh superadmin', 'StockReset', null
                    );
                    $affected++;
                }
            } else {
                foreach ($data['items'] as $item) {
                    $variant = ProductVariant::find($item['variant_id']);
                    if (!$variant) {
                        continue;
                    }
                    $target  = (int) $item['qty'];
                    $current = (int) (Stock::where('product_variant_id', $variant->id)
                        ->where('location_type', $locType)
                        ->where('location_id', $locId)
                        ->value('qty') ?? 0);
                    $delta = $target - $current;
                    if ($delta === 0) {
                        continue;
                    }
                    StockService::mutate(
                        $variant, $locType, $locId, $delta,
                        'adjust', 'Reset stok (sebagian) oleh superadmin', 'StockReset', null
                    );
                    $affected++;
                }
            }
        });

        AuditLogService::log(
            'reset_stock',
            'Stok',
            "Reset stok mode '{$data['mode']}' di {$locType} #{$locId} ({$location->name}) — {$affected} varian diperbarui.",
            null,
            [
                'location_type' => $locType,
                'location_id'   => $locId,
                'mode'          => $data['mode'],
                'target_qty'    => $data['target_qty'] ?? null,
                'affected'      => $affected,
            ]
        );

        return back()->with('success', "Reset stok selesai di {$location->name}. {$affected} varian diperbarui (tercatat di ledger).");
    }
}
