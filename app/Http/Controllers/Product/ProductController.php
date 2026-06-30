<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Size;
use App\Services\AuditLogService;
use App\Services\SkuGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view product');

        $user = Auth::user();
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'desc';
        }

        $query = Product::with(['brand', 'category', 'productType', 'images'])
            ->listingFilters($request)
            ->when($request->status !== null && $request->status !== '', fn($q) =>
                $q->where('is_active', $request->status));

        if ($sortBy === 'stock') {
            $query->withSum(['stocks as total_stock' => Product::roleStockConstraint($user)], 'qty')
                ->orderBy('total_stock', $sortDir)
                ->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('created_at', $sortDir);
        }

        $products    = $query->paginate(24)->withQueryString();
        $brands      = Brand::active()->orderBy('name')->get();
        $categories  = Category::whereNull('parent_id')->with('children')->orderBy('name')->get();
        $productTypes = ProductType::orderBy('name')->get();

        return view('products.index', compact('products', 'brands', 'categories', 'productTypes'));
    }

    public function catalogExport(Request $request): View
    {
        $this->authorize('view product');

        $products = Product::with(['brand', 'category', 'productType', 'images', 'variants.color', 'variants.size'])
            ->listingFilters($request)
            ->when($request->status !== null && $request->status !== '', fn($q) =>
                $q->where('is_active', $request->status))
            ->orderBy('name')
            ->get();

        return view('products.catalog_export', compact('products'));
    }

    public function create(): View
    {
        $this->authorize('create product');

        $brands      = Brand::active()->orderBy('name')->get();
        $categories  = Category::with('children')->whereNull('parent_id')->orderBy('name')->get();
        $productTypes = ProductType::orderBy('name')->get();
        $colors      = Color::orderBy('name')->get();
        $sizes       = Size::orderBy('sort_order')->get();

        return view('products.create', compact('brands', 'categories', 'productTypes', 'colors', 'sizes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create product');

        $data = $request->validate([
            'brand_id'        => 'required|exists:brands,id',
            'category_id'     => 'required|exists:categories,id',
            'product_type_id' => 'required|exists:product_types,id',
            'name'            => 'required|string|max:200',
            'description'     => 'nullable|string',
            'base_price'      => 'required|numeric|min:0',
            'sell_price'      => 'required|numeric|min:0',
            'retail_price'    => 'required|numeric|min:0',
            'is_active'       => 'boolean',
            'images'          => 'nullable|array',
            'images.*'        => 'image|max:10240',
            'primary_image'   => 'nullable|integer',
            'color_id_primary'=> 'nullable|exists:colors,id',
        ]);

        DB::transaction(function () use ($data, $request) {
            $brand      = Brand::findOrFail($data['brand_id']);
            $modelCode  = SkuGeneratorService::generateModelCode($brand->code);

            $product = Product::create([
                'brand_id'        => $data['brand_id'],
                'category_id'     => $data['category_id'],
                'product_type_id' => $data['product_type_id'],
                'name'            => $data['name'],
                'model_code'      => $modelCode,
                'description'     => $data['description'] ?? null,
                'base_price'      => $data['base_price'],
                'sell_price'      => $data['sell_price'],
                'retail_price'    => $data['retail_price'],
                'is_active'       => $request->boolean('is_active', true),
                'created_by'      => Auth::id(),
            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $idx => $file) {
                    $path = $file->store('products/' . $product->id, 'public');
                    $product->images()->create([
                        'color_id'   => $data['color_id_primary'] ?? null,
                        'path'       => $path,
                        'is_primary' => ($idx === (int) ($data['primary_image'] ?? 0)),
                        'sort_order' => $idx,
                    ]);
                }
            }

            AuditLogService::log('create', 'products', "Produk '{$product->name}' ({$modelCode}) dibuat",
                null, $product->toArray(), Product::class, $product->id);
        });

        return redirect()->route('products.index')->with('success', 'Produk berhasil dibuat.');
    }

    public function show(Product $product): View
    {
        $this->authorize('view product');
        $user = Auth::user();

        $product->load([
            'brand', 'category', 'productType', 'images',
            'variants.color', 'variants.size',
            // Filter stok berdasarkan Role User
            'variants.stocks' => Product::roleStockConstraint($user),
        ]);

        $colors = Color::orderBy('name')->get();
        $sizes  = Size::orderBy('sort_order')->get();

        return view('products.show', compact('product', 'colors', 'sizes'));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update product');
        $product->load(['images']);

        $brands       = Brand::active()->orderBy('name')->get();
        $categories   = Category::with('children')->whereNull('parent_id')->orderBy('name')->get();
        $productTypes = ProductType::orderBy('name')->get();
        $colors       = Color::orderBy('name')->get();

        return view('products.edit', compact('product', 'brands', 'categories', 'productTypes', 'colors'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update product');

        $data = $request->validate([
            'brand_id'        => 'required|exists:brands,id',
            'category_id'     => 'required|exists:categories,id',
            'product_type_id' => 'required|exists:product_types,id',
            'name'            => 'required|string|max:200',
            'description'     => 'nullable|string',
            'base_price'      => 'required|numeric|min:0',
            'sell_price'      => 'required|numeric|min:0',
            'retail_price'    => 'required|numeric|min:0',
            'images'          => 'nullable|array',
            'images.*'        => 'image|max:10240',
            'primary_image'   => 'nullable|integer',
            'delete_images'   => 'nullable|array',
            'delete_images.*' => 'integer',
        ]);

        $old = $product->toArray();

        DB::transaction(function () use ($data, $request, $product, $old) {
            $product->update([
                'brand_id'        => $data['brand_id'],
                'category_id'     => $data['category_id'],
                'product_type_id' => $data['product_type_id'],
                'name'            => $data['name'],
                'description'     => $data['description'] ?? null,
                'base_price'      => $data['base_price'],
                'sell_price'      => $data['sell_price'],
                'retail_price'    => $data['retail_price'],
                'is_active'       => $request->boolean('is_active'),
            ]);

            // Delete marked images
            if (!empty($data['delete_images'])) {
                $toDelete = $product->images()->whereIn('id', $data['delete_images'])->get();
                foreach ($toDelete as $img) {
                    Storage::disk('public')->delete($img->path);
                    $img->delete();
                }
            }

            // Upload new images
            if ($request->hasFile('images')) {
                $existingCount = $product->images()->count();
                foreach ($request->file('images') as $idx => $file) {
                    $path = $file->store('products/' . $product->id, 'public');
                    $product->images()->create([
                        'path'       => $path,
                        'is_primary' => ($existingCount === 0 && $idx === 0),
                        'sort_order' => $existingCount + $idx,
                    ]);
                }
            }

            AuditLogService::log('update', 'products', "Produk '{$product->name}' diperbarui",
                $old, $product->fresh()->toArray(), Product::class, $product->id);
        });

        return redirect()->route('products.show', $product)->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete product');

        if ($product->variants()->exists()) {
            return back()->with('error', 'Produk tidak dapat dihapus karena masih memiliki varian.');
        }

        AuditLogService::log('delete', 'products', "Produk '{$product->name}' dihapus",
            $product->toArray(), null, Product::class, $product->id);

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk dihapus.');
    }
}
