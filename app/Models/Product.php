<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'brand_id', 'category_id', 'product_type_id',
        'name', 'model_code', 'description',
        'base_price', 'sell_price', 'retail_price', 'is_active', 'created_by','reward_store',
        'reward_owner',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'is_active'  => 'boolean',
    ];

    public function brand(): BelongsTo      { return $this->belongsTo(Brand::class); }
    public function category(): BelongsTo   { return $this->belongsTo(Category::class); }
    public function productType(): BelongsTo { return $this->belongsTo(ProductType::class); }
    public function creator(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }
    public function variants(): HasMany     { return $this->hasMany(ProductVariant::class); }
    protected static function booted()
    {
        static::deleting(function ($product) {
            // Jika produk di-force delete, force delete juga variannya
            if ($product->isForceDeleting()) {
                $product->variants()->forceDelete();
            } else {
                // Jika produk di-soft delete, soft delete juga variannya
                $product->variants()->delete();
            }
        });
    }
    public function images(): HasMany       { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function stocks(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Stock::class, ProductVariant::class);
    }

    public function primaryImage(): ?ProductImage
    {
        return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
    }

    public function scopeActive($query)     { return $query->where('is_active', true); }
    public function scopeSearch($query, $term)
    {
        return $query->where(fn($q) =>
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('model_code', 'like', "%{$term}%")
        );
    }

    /**
     * Filter daftar produk (brand/kategori/tipe + pencarian nama/model/SKU varian).
     * Dipakai bersama oleh ProductController & CatalogController.
     */
    public function scopeListingFilters($query, \Illuminate\Http\Request $request)
    {
        return $query
            ->when($request->brand_id, fn($q) => $q->where('brand_id', $request->brand_id))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->product_type_id, fn($q) => $q->where('product_type_id', $request->product_type_id))
            ->when($request->search, function ($q) use ($request) {
                $term = $request->search;
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('model_code', 'like', "%{$term}%")
                        ->orWhereHas('variants', fn($vq) => $vq->where('sku', 'like', "%{$term}%"));
                });
            });
    }

    /**
     * Closure eager-load stok yang dibatasi sesuai role user
     * (kepala toko → tokonya, admin gudang → gudangnya, lainnya → semua).
     * Dipakai bersama oleh Catalog, Product, dan Dashboard.
     */
    public static function roleStockConstraint(?User $user): \Closure
    {
        return function ($q) use ($user) {
            if ($user && $user->hasRole('kepala toko')) {
                $q->where('location_type', 'store')
                  ->whereIn('location_id', $user->stores()->pluck('stores.id'));
            } elseif ($user && $user->hasRole('admin gudang')) {
                $q->where('location_type', 'warehouse')
                  ->whereIn('location_id', $user->warehouses()->pluck('warehouses.id'));
            }
            // superadmin/owner/finance: tanpa filter (lihat semua stok)
        };
    }
}
