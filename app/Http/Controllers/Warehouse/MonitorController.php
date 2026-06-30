<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MonitorController extends Controller
{
    public function index(): View
    {
        $this->authorize('view warehouse dashboard');

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        // Top 20 low-stock SKUs across all warehouses
        $lowStock = Stock::with(['variant.product.brand', 'variant.color', 'variant.size', 'location'])
            ->where('location_type', 'warehouse')
            ->where('qty', '<=', 5)
            ->orderBy('qty')
            ->take(20)
            ->get();

        // Active shipments (dari proses awal sampai tiba)
        $inTransit = Shipment::with(['store', 'warehouse'])
            ->whereIn('status', ['prepared', 'packed', 'shipped', 'arrived'])
            ->latest('updated_at')
            ->get();

        // Recent inbounds today
        $todayInbounds = \App\Models\Inbound::with(['warehouse'])
            ->where('status', 'received')
            ->whereDate('received_at', today())
            ->count();

        // Get Stores and Brands for the new monitor section
        $stores = \App\Models\Store::where('is_active', true)->orderBy('name')->get();
        $brands = \App\Models\Brand::where('is_active', true)->orderBy('name')->take(6)->get(); // Kita batasi 6 brand sesuai request

        return view('warehouse.monitor', compact('warehouses', 'lowStock', 'inTransit', 'todayInbounds', 'stores', 'brands'));
    }

    public function poll(): JsonResponse
    {
        $this->authorize('view warehouse dashboard');

        $lowStockCount = Stock::where('location_type', 'warehouse')
            ->where('qty', '<=', 5)
            ->where('qty', '>', 0)
            ->count();

        $inTransitCount = Shipment::whereIn('status', ['shipped', 'arrived'])->count();

        $todayInbounds = \App\Models\Inbound::where('status', 'received')
            ->whereDate('received_at', today())
            ->count();

        $lowStockItems = Stock::with(['variant.product', 'variant.color', 'variant.size'])
            ->where('location_type', 'warehouse')
            ->where('qty', '<=', 5)
            ->where('qty', '>', 0)
            ->orderBy('qty')
            ->take(10)
            ->get()
            ->map(fn($s) => [
                'sku'  => $s->variant->sku,
                'name' => $s->variant->product->name . ' · ' . $s->variant->color->name . ' / ' . $s->variant->size->name,
                'qty'  => $s->qty,
            ]);

        return response()->json([
            'low_stock_count'  => $lowStockCount,
            'in_transit_count' => $inTransitCount,
            'today_inbounds'   => $todayInbounds,
            'low_stock_items'  => $lowStockItems,
            'polled_at'        => now()->format('H:i:s'),
        ]);
    }
}
