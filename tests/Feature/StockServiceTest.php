<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\Stock;
use App\Models\StockLedger;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeVariant(): ProductVariant
    {
        $brand = Brand::create(['name' => 'Nike', 'code' => 'NK', 'slug' => 'nike-' . uniqid()]);
        $category = Category::create(['name' => 'Shoes', 'code' => 'SH', 'slug' => 'shoes-' . uniqid()]);
        $type = ProductType::create(['name' => 'Sneaker', 'code' => 'SN', 'slug' => 'sneaker-' . uniqid()]);
        $color = Color::create(['name' => 'Black', 'code' => 'BLK']);
        $size = Size::create(['name' => '42', 'code' => '42']);

        $product = Product::create([
            'brand_id'        => $brand->id,
            'category_id'     => $category->id,
            'product_type_id' => $type->id,
            'name'            => 'Air Test',
            'model_code'      => 'A001',
            'base_price'      => 80000,
            'sell_price'      => 100000,
            'retail_price'    => 120000,
            'is_active'       => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'color_id'   => $color->id,
            'size_id'    => $size->id,
            'sku'        => 'NK-A001-BLK-42',
            'is_active'  => true,
        ]);
    }

    public function test_stock_in_increments_qty_and_writes_ledger(): void
    {
        $variant = $this->makeVariant();
        $warehouse = Warehouse::create(['name' => 'Gudang 1', 'code' => 'GD1']);

        StockService::mutate($variant, 'warehouse', $warehouse->id, 10, 'in', 'Tes masuk');

        $this->assertSame(10, (int) Stock::where('product_variant_id', $variant->id)
            ->where('location_type', 'warehouse')->where('location_id', $warehouse->id)->value('qty'));

        $ledger = StockLedger::where('product_variant_id', $variant->id)->first();
        $this->assertNotNull($ledger);
        $this->assertSame(10, (int) $ledger->qty);
        $this->assertSame(0, (int) $ledger->qty_before);
        $this->assertSame(10, (int) $ledger->qty_after);
    }

    public function test_stock_out_decrements_qty(): void
    {
        $variant = $this->makeVariant();
        $warehouse = Warehouse::create(['name' => 'Gudang 1', 'code' => 'GD1']);

        StockService::mutate($variant, 'warehouse', $warehouse->id, 10, 'in');
        StockService::mutate($variant, 'warehouse', $warehouse->id, -3, 'sale');

        $this->assertSame(7, (int) Stock::where('product_variant_id', $variant->id)->value('qty'));
        $this->assertSame(2, StockLedger::where('product_variant_id', $variant->id)->count());
    }

    public function test_stock_cannot_go_negative(): void
    {
        $variant = $this->makeVariant();
        $warehouse = Warehouse::create(['name' => 'Gudang 1', 'code' => 'GD1']);

        StockService::mutate($variant, 'warehouse', $warehouse->id, 2, 'in');

        $this->expectException(\RuntimeException::class);

        try {
            StockService::mutate($variant, 'warehouse', $warehouse->id, -5, 'sale');
        } finally {
            // Stok harus tetap 2 dan tidak ada ledger pengurangan yang tertulis.
            $this->assertSame(2, (int) Stock::where('product_variant_id', $variant->id)->value('qty'));
            $this->assertSame(1, StockLedger::where('product_variant_id', $variant->id)->count());
        }
    }
}
