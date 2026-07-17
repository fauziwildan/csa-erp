<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnlineOrder;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\ReferenceNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Endpoint publik (read-only) untuk landing page katalog eksternal.
 * Hanya mengembalikan produk + stok GUDANG untuk sebuah brand. Tidak ada data sensitif.
 */
class PublicCatalogController extends Controller
{
    public function index(string $brand = 'WK')
    {
        $brand = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $brand));

        // Cache array biasa (bukan object Collection) agar aman di-serialize/unserialize.
        $payload = Cache::remember("public_catalog_{$brand}", 60, function () use ($brand) {
            $items = DB::table('products as p')
                ->join('brands as b', 'p.brand_id', '=', 'b.id')
                ->join('product_variants as pv', 'pv.product_id', '=', 'p.id')
                ->join('colors as c', 'pv.color_id', '=', 'c.id')
                ->join('sizes as sz', 'pv.size_id', '=', 'sz.id')
                ->join('stocks as s', 's.product_variant_id', '=', 'pv.id')
                ->whereNull('p.deleted_at')->where('p.is_active', 1)
                ->whereNull('pv.deleted_at')->where('pv.is_active', 1)
                ->where('s.location_type', 'warehouse')->where('s.qty', '>', 0)
                ->where('b.code', $brand)
                ->orderBy('p.name')->orderBy('c.name')->orderBy('sz.sort_order')
                ->selectRaw(
                    "p.id as product_id, p.name, p.model_code, "
                    . "(p.sell_price + pv.price_adjustment) as price, "
                    . "(SELECT path FROM product_images WHERE product_id = p.id "
                    . " ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as image, "
                    . "pv.sku, c.name as color, c.hex_code, sz.name as size, sz.sort_order as size_order, s.qty"
                )
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();

            // Semua gambar (galeri) tiap produk → untuk slider di katalog
            $productIds = array_values(array_unique(array_column($items, 'product_id')));
            $imagesByProduct = [];
            if ($productIds) {
                $imgs = DB::table('product_images')
                    ->whereIn('product_id', $productIds)
                    ->orderByDesc('is_primary')->orderBy('sort_order')
                    ->get(['product_id', 'path']);
                foreach ($imgs as $im) {
                    $imagesByProduct[(int) $im->product_id][] = $im->path;
                }
            }

            return ['items' => $items, 'images' => $imagesByProduct];
        });

        return response()
            ->json([
                'brand'        => $brand,
                'generated_at' => now()->toIso8601String(),
                'count'        => count($payload['items']),
                'items'        => $payload['items'],
                'images'       => (object) $payload['images'],
            ])
            ->header('Access-Control-Allow-Origin', '*'); // izinkan diakses lintas domain
    }

    /**
     * Terima pesanan dari landing page katalog (server-to-server, diamankan token).
     * Order disimpan status "pending" — stok BARU dipotong saat superadmin konfirmasi bayar.
     */
    public function storeOrder(Request $request)
    {
        $token = config('services.catalog.token');
        if (empty($token)) {
            return response()->json(['message' => 'Endpoint order belum dikonfigurasi (CATALOG_API_TOKEN kosong).'], 503);
        }
        if (! hash_equals((string) $token, (string) $request->header('X-Catalog-Token'))) {
            return response()->json(['message' => 'Token tidak valid.'], 401);
        }

        $data = $request->validate([
            'customer_name'  => 'required|string|max:150',
            'customer_phone' => 'nullable|string|max:30',
            'address'        => 'required|string|max:1000',
            'notes'          => 'nullable|string|max:500',
            'items'          => 'required|array|min:1|max:50',
            'items.*.sku'    => 'required|string|max:100',
            'items.*.qty'    => 'required|integer|min:1|max:999',
        ]);

        $warehouse = Warehouse::where('is_active', true)->orderBy('id')->first();
        if (! $warehouse) {
            return response()->json(['message' => 'Gudang aktif tidak tersedia.'], 422);
        }

        try {
            $order = DB::transaction(function () use ($data, $warehouse) {
                $order = OnlineOrder::create([
                    'order_no'       => ReferenceNumberService::onlineOrder(),
                    'customer_name'  => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'address'        => $data['address'],
                    'notes'          => $data['notes'] ?? null,
                    'warehouse_id'   => $warehouse->id,
                    'status'         => 'pending',
                    'payment_note'   => 'Transfer saat barang sampai',
                    'source'         => 'catalog',
                ]);

                $total = 0;
                $totalQty = 0;

                foreach ($data['items'] as $row) {
                    $variant = ProductVariant::with(['product', 'color', 'size'])
                        ->where('sku', $row['sku'])->first();
                    if (! $variant) {
                        throw new \RuntimeException("SKU {$row['sku']} tidak ditemukan.");
                    }

                    $qty = (int) $row['qty'];
                    $stock = Stock::where('product_variant_id', $variant->id)
                        ->where('location_type', 'warehouse')
                        ->where('location_id', $warehouse->id)
                        ->value('qty') ?? 0;
                    if ($stock < $qty) {
                        throw new \RuntimeException("Stok {$variant->sku} tidak cukup (tersisa {$stock}).");
                    }

                    // Harga diambil dari DB — tidak mempercayai harga kiriman client.
                    $price = $variant->sellPrice();
                    $sub   = $price * $qty;

                    $order->items()->create([
                        'product_variant_id' => $variant->id,
                        'sku'                => $variant->sku,
                        'product_name'       => $variant->product->name,
                        'color'              => $variant->color?->name,
                        'size'               => $variant->size?->name,
                        'qty'                => $qty,
                        'unit_price'         => $price,
                        'subtotal'           => $sub,
                    ]);

                    $total += $sub;
                    $totalQty += $qty;
                }

                $order->update(['total_amount' => $total, 'total_qty' => $totalQty]);

                return $order->fresh('items');
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'order_no'     => $order->order_no,
            'total_amount' => (float) $order->total_amount,
            'total_qty'    => $order->total_qty,
            'items'        => $order->items->map(fn ($i) => [
                'sku' => $i->sku, 'product_name' => $i->product_name,
                'color' => $i->color, 'size' => $i->size,
                'qty' => $i->qty, 'unit_price' => (float) $i->unit_price,
                'subtotal' => (float) $i->subtotal,
            ]),
        ], 201);
    }
}
