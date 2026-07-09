<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Support\Facades\Auth;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseStockController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view warehouse');

        $user = Auth::user();
        if ($user->hasRole(['superadmin', 'owner'])) {
            $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        } else {
            $warehouses = $user->warehouses()->where('is_active', true)->orderBy('name')->get();
        }
        
        $warehouseId = $request->warehouse_id ?? $warehouses->first()?->id;

        if ($request->warehouse_id && !$warehouses->contains('id', $request->warehouse_id)) {
            $warehouseId = $warehouses->first()?->id;
        }

        $query = Stock::with(['variant.product.brand', 'variant.product.images', 'variant.image', 'variant.color', 'variant.size'])
            ->where('location_type', 'warehouse')
            ->where('location_id', $warehouseId)
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

        $stocks  = $query->orderByDesc('qty')->paginate(50)->withQueryString();
        $brands  = Brand::active()->orderBy('name')->get();
        $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;

        return view('warehouse.stock.index', compact('stocks', 'warehouses', 'brands', 'warehouse', 'warehouseId'));
    }
}
