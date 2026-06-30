<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = in_array($request->input('sort_dir'), ['asc', 'desc']) ? $request->input('sort_dir') : 'desc';

        $stockConstraint = Product::roleStockConstraint($user);

        $query = Product::with(['brand', 'category', 'images', 'variants.stocks' => $stockConstraint])
            ->where('is_active', true)
            ->listingFilters($request);

        if ($sortBy === 'stock') {
            $query->withSum(['stocks as total_stock' => $stockConstraint], 'qty')
                ->orderBy('total_stock', $sortDir)
                ->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('created_at', $sortDir);
        }

        $products   = $query->paginate(24)->withQueryString();
        $brands     = Brand::active()->orderBy('name')->get();
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();

        return view('catalog.index', compact('products', 'brands', 'categories'));
    }

    public function show(ProductVariant $productVariant)
    {
        $user = Auth::user();

        $productVariant->load([
            'product.brand', 'product.category', 'product.images',
            'product.variants.color', 'product.variants.size', 'color', 'size',
            'product.variants.stocks' => Product::roleStockConstraint($user),
        ]);

        return view('catalog.show', compact('productVariant'));
    }
}
