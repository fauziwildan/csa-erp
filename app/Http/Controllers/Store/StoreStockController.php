<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Stock;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StoreStockController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view store');
        $user  = Auth::user();

        if ($user->hasRole(['superadmin', 'owner', 'admin gudang'])) {
            $stores = Store::where('is_active', true)->orderBy('name')->get();
            $storeId = $request->store_id ?? $stores->first()?->id;
        } else {
            $stores = $user->stores()->where('is_active', true)->orderBy('name')->get();
            $storeId  = $request->store_id
                ? $stores->firstWhere('id', $request->store_id)?->id ?? $stores->first()?->id
                : ($stores->firstWhere('pivot.is_primary', true)?->id ?? $stores->first()?->id);
            
            // Security measure: if they manually change the URL to another store, override it.
            if ($request->store_id && !$stores->contains('id', $request->store_id)) {
                $storeId = $stores->first()?->id;
            }
        }

        $query = Stock::with(['variant.product.brand', 'variant.product.images', 'variant.image', 'variant.color', 'variant.size'])
            ->where('location_type', 'store')
            ->where('location_id', $storeId)
            ->where('qty', '>', 0)
            ->whereHas('variant.product');

        if ($request->brand_id) {
            $query->whereHas('variant.product', fn($q) => $q->where('brand_id', $request->brand_id));
        }
        if ($request->search) {
            $term = $request->search;
            $query->whereHas('variant', fn($q) =>
                $q->where('sku', 'like', "%{$term}%")
                  ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$term}%"))
            );
        }

        $stocks    = $query->orderByDesc('qty')->paginate(50)->withQueryString();
        $brands    = Brand::active()->orderBy('name')->get();
        $store     = $storeId ? Store::find($storeId) : null;

        return view('store.stock.index', compact('stocks', 'stores', 'brands', 'store', 'storeId'));
    }
}
