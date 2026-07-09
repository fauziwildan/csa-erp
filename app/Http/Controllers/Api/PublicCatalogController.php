<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $items = Cache::remember("public_catalog_{$brand}", 60, function () use ($brand) {
            return DB::table('products as p')
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
        });

        return response()
            ->json([
                'brand'        => $brand,
                'generated_at' => now()->toIso8601String(),
                'count'        => count($items),
                'items'        => $items,
            ])
            ->header('Access-Control-Allow-Origin', '*'); // izinkan diakses lintas domain
    }
}
