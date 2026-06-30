<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CustomerReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\Store;
use App\Models\Warehouse;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();

        if ($user->hasRole('kasir')) {
        return redirect()->route('pos.index'); // atau url('pos/session')
    }
        // ====================================================================
        // 1. DASHBOARD GUDANG (Admin Gudang & Operator Gudang)
        // ====================================================================
        if ($user->hasRole('admin gudang') || $user->hasRole('operator gudang')) {
            $warehouseIds = $user->warehouses()->pluck('warehouses.id');
            
            $today = now()->toDateString();
            $lowStock = Stock::where('location_type', 'warehouse')->whereIn('location_id', $warehouseIds)->where('qty', '<=', 5)->count();
            $totalWarehouseStock = Stock::where('location_type', 'warehouse')->whereIn('location_id', $warehouseIds)->sum('qty');
            $todayExpense = Expense::whereIn('warehouse_id', $warehouseIds)->whereDate('expense_date', $today)->sum('amount');
            
            $products = Product::with(['brand', 'category', 'images', 'variants.stocks' => function($q) use ($warehouseIds) {
                $q->where('location_type', 'warehouse')->whereIn('location_id', $warehouseIds);
            }])->latest()->take(10)->get();

            $latestOpname = \App\Models\StockOpname::with(['items.variant.product', 'items.variant.color', 'items.variant.size', 'creator'])
                ->where('location_type', 'warehouse')
                ->whereIn('location_id', $warehouseIds)
                ->latest()
                ->first();
            
            return view('dashboard.warehouse', compact('lowStock', 'totalWarehouseStock', 'todayExpense', 'products', 'latestOpname'));
        }

        // ====================================================================
        // 2. DASHBOARD KEPALA TOKO
        // ====================================================================
        if ($user->hasRole('kepala toko')) {
            $storeIds = $user->stores()->pluck('stores.id');
            
            $today = now()->toDateString();
            $todaySalesGross = Sale::whereIn('store_id', $storeIds)->whereDate('created_at', $today)->sum('total_amount');
            $todayRefunds = CustomerReturn::whereIn('store_id', $storeIds)->whereDate('created_at', $today)->sum('refund_amount');
            $todaySales = max(0, $todaySalesGross - $todayRefunds);
            
            $todayOrders = Sale::whereIn('store_id', $storeIds)->whereDate('created_at', $today)->count();
            $todayExpense = Expense::whereIn('store_id', $storeIds)->whereDate('expense_date', $today)->sum('amount');
            $todayProfit = $todaySales - $todayExpense;

            // TAMBAHAN: Ambil 10 produk yang HANYA ADA STOKNYA di toko milik user ini
            $products = Product::with(['brand', 'category', 'images', 'variants.stocks' => function($q) use ($storeIds) {
                $q->where('location_type', 'store')->whereIn('location_id', $storeIds);
            }])
            ->whereHas('variants.stocks', function ($q) use ($storeIds) {
                $q->where('location_type', 'store')
                  ->whereIn('location_id', $storeIds)
                  ->where('qty', '>', 0);
            })
            ->latest()->take(10)->get();
            
            $approachingDueSales = Sale::with(['store'])
                ->whereIn('store_id', $storeIds)
                ->whereNotNull('customer_name')
                ->where('customer_name', '!=', '')
                ->where('payment_status', '!=', 'lunas')
                ->whereNotNull('due_date')
                ->orderBy('due_date', 'asc')
                ->limit(5)
                ->get();
            
            $latestOpname = \App\Models\StockOpname::with(['items.variant.product', 'items.variant.color', 'items.variant.size', 'creator'])
                ->where('location_type', 'store')
                ->whereIn('location_id', $storeIds)
                ->latest()
                ->first();
            
            return view('dashboard.store', compact('todaySales', 'todayOrders', 'todayExpense', 'todayProfit', 'products', 'approachingDueSales', 'latestOpname'));
        }

        // ====================================================================
        // 3. DASHBOARD FINANCE
        // ====================================================================
        if ($user->hasRole('finance')) {
            // Ambil metrik arus kas bulan ini
            $thisMonth = now()->format('Y-m');
            $monthSalesGross = Sale::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$thisMonth])->sum('total_amount');
            $monthRefunds = CustomerReturn::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$thisMonth])->sum('refund_amount');
            $monthSales = max(0, $monthSalesGross - $monthRefunds);
            
            return view('dashboard.finance', compact('monthSales'));
        }

        // ====================================================================
        // 4. DASHBOARD SUPER ADMIN / OWNER (MENGGUNAKAN KODE LAMA ANDA)
        // ====================================================================
        
        $storeId = $request->query('store_id');
        $stores  = Store::where('is_active', true)->orderBy('name')->get();

        $today     = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $thisMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');

        // ── Existing stats (Global) ──────────────────────────
        $stats = [
            'brands'     => Brand::active()->count(),
            'warehouses' => Warehouse::where('is_active', true)->count(),
            'stores'     => Store::where('is_active', true)->count(),
            'products'   => Product::active()->count(),
            'variants'   => ProductVariant::where('is_active', true)->count(),
        ];

        // ── Financial KPIs ───────────────────────────────────
        $todaySalesGross     = Sale::when($storeId, fn($q) => $q->where('store_id', $storeId))->whereDate('created_at', $today)->sum('total_amount');
        $todayRefunds        = CustomerReturn::when($storeId, fn($q) => $q->where('store_id', $storeId))->whereDate('created_at', $today)->sum('refund_amount');
        $todaySales          = max(0, $todaySalesGross - $todayRefunds);

        $yesterdaySalesGross = Sale::when($storeId, fn($q) => $q->where('store_id', $storeId))->whereDate('created_at', $yesterday)->sum('total_amount');
        $yesterdayRefunds    = CustomerReturn::when($storeId, fn($q) => $q->where('store_id', $storeId))->whereDate('created_at', $yesterday)->sum('refund_amount');
        $yesterdaySales      = max(0, $yesterdaySalesGross - $yesterdayRefunds);

        $monthSalesGross     = Sale::when($storeId, fn($q) => $q->where('store_id', $storeId))->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$thisMonth])->sum('total_amount');
        $monthRefunds        = CustomerReturn::when($storeId, fn($q) => $q->where('store_id', $storeId))->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$thisMonth])->sum('refund_amount');
        $monthSales          = max(0, $monthSalesGross - $monthRefunds);

        $lastMonthSalesGross = Sale::when($storeId, fn($q) => $q->where('store_id', $storeId))->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$lastMonth])->sum('total_amount');
        $lastMonthRefunds    = CustomerReturn::when($storeId, fn($q) => $q->where('store_id', $storeId))->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$lastMonth])->sum('refund_amount');
        $lastMonthSales      = max(0, $lastMonthSalesGross - $lastMonthRefunds);

        $todayOrders    = Sale::when($storeId, fn($q) => $q->where('store_id', $storeId))->whereDate('created_at', $today)->count();
        $monthOrders    = Sale::when($storeId, fn($q) => $q->where('store_id', $storeId))->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$thisMonth])->count();

        // ── Top Selling Products ─────────────────────────────
        $topDateFilter = $request->query('top_date_filter', 'this_month');
        $topProductsQuery = SaleItem::select(
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(sale_items.qty) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->join('product_variants', 'sale_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->when($storeId, fn($q) => $q->where('sales.store_id', $storeId));

        if ($topDateFilter === 'today') {
            $topProductsQuery->whereDate('sales.created_at', now()->toDateString());
        } elseif ($topDateFilter === '7_days') {
            $topProductsQuery->where('sales.created_at', '>=', now()->subDays(6)->startOfDay());
        } elseif ($topDateFilter === '30_days') {
            $topProductsQuery->where('sales.created_at', '>=', now()->subDays(29)->startOfDay());
        } else {
            // this_month
            $topProductsQuery->whereRaw("DATE_FORMAT(sales.created_at, '%Y-%m') = ?", [$thisMonth]);
        }

        $topProducts = $topProductsQuery->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // ── Revenue & Orders per day (last 30 days) ──────────
        $rawRevenue = Sale::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('revenue', 'date');

        $rawOrders = Sale::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_orders')
            )
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total_orders', 'date');

        // Fill gaps so chart has continuous 30-day data
        $chartLabels  = [];
        $chartRevenue = [];
        $chartOrders  = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $chartLabels[]  = Carbon::parse($d)->format('d M');
            $chartRevenue[] = (float) ($rawRevenue[$d] ?? 0);
            $chartOrders[]  = (int)   ($rawOrders[$d]  ?? 0);
        }

        // ── Sales by Store ───────────────────────────────────
        $storeDateFilter = $request->query('store_date_filter', '30_days');
        
        $salesByStoreQuery = Sale::select(
                'stores.name as store_name',
                DB::raw('SUM(sales.total_amount) as total_revenue'),
                DB::raw('COUNT(sales.id) as total_orders')
            )
            ->join('stores', 'sales.store_id', '=', 'stores.id')
            ->when($storeId, fn($q) => $q->where('sales.store_id', $storeId));

        if ($storeDateFilter === 'today') {
            $salesByStoreQuery->whereDate('sales.created_at', now()->toDateString());
        } elseif ($storeDateFilter === '7_days') {
            $salesByStoreQuery->where('sales.created_at', '>=', now()->subDays(6)->startOfDay());
        } elseif ($storeDateFilter === 'this_month') {
            $salesByStoreQuery->whereRaw("DATE_FORMAT(sales.created_at, '%Y-%m') = ?", [$thisMonth]);
        } else {
            // default 30_days
            $salesByStoreQuery->where('sales.created_at', '>=', now()->subDays(29)->startOfDay());
        }

        $salesByStore = $salesByStoreQuery->groupBy('stores.name')
            ->orderByDesc('total_revenue')
            ->get();

        // ── Payment Method Distribution (this month) ─────────
        // Akurat untuk SPLIT PAYMENT: nominal per metode diambil dari sale_payments
        // (uang aktual yang diterima per metode), bukan dari metode utama nota.
        $paySplit = \App\Models\SalePayment::select(
                'payment_methods.name as method_name',
                DB::raw('COUNT(DISTINCT sale_payments.sale_id) as total_count'),
                DB::raw('SUM(sale_payments.amount) as total_amount')
            )
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->when($storeId, fn($q) => $q->where('sales.store_id', $storeId))
            ->whereRaw("DATE_FORMAT(sales.created_at, '%Y-%m') = ?", [$thisMonth])
            ->groupBy('payment_methods.name')
            ->get();

        // Nota lama (sebelum fitur split) tanpa baris sale_payments → fallback ke metode utama.
        $payLegacy = Sale::select(
                'payment_methods.name as method_name',
                DB::raw('COUNT(sales.id) as total_count'),
                DB::raw('SUM(sales.total_amount) as total_amount')
            )
            ->join('payment_methods', 'sales.payment_method_id', '=', 'payment_methods.id')
            ->whereDoesntHave('payments')
            ->when($storeId, fn($q) => $q->where('sales.store_id', $storeId))
            ->whereRaw("DATE_FORMAT(sales.created_at, '%Y-%m') = ?", [$thisMonth])
            ->groupBy('payment_methods.name')
            ->get();

        // Gabung kedua sumber per nama metode.
        $paymentDistribution = $paySplit->concat($payLegacy)
            ->groupBy('method_name')
            ->map(fn ($rows, $name) => (object) [
                'method_name'  => $name,
                'total_count'  => $rows->sum('total_count'),
                'total_amount' => $rows->sum('total_amount'),
            ])
            ->sortByDesc('total_count')
            ->values();

        // ── Stock Value (reuse FinanceController pattern) ────
        $storeStockValue = Stock::where('location_type', 'store')
            ->when($storeId, fn($q) => $q->where('location_id', $storeId))
            ->join('product_variants', 'stocks.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select(DB::raw('SUM(stocks.qty * (products.sell_price + product_variants.price_adjustment)) as value'))
            ->value('value') ?? 0;

        $warehouseStockValue = Stock::where('location_type', 'warehouse')
            ->join('product_variants', 'stocks.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select(DB::raw('SUM(stocks.qty * (products.sell_price + product_variants.price_adjustment)) as value'))
            ->value('value') ?? 0;

        // ── Returns Overview ─────────────────────────────────
        $monthReturns   = CustomerReturn::when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$thisMonth])->count();
        $pendingReturns = CustomerReturn::when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where('status', 'pending')->count();

        $currentYear = now()->year;
        
        $rewardQuery = SaleItem::whereHas('sale', function($q) use ($currentYear, $storeId) {
            $q->whereYear('created_at', $currentYear);
            if ($storeId) {
                $q->where('store_id', $storeId);
            }
        });

        // Volume barang terjual tetap accrual (semua penjualan tahun ini).
        $totalItemsSold = (clone $rewardQuery)->sum('qty');
        // Komisi: cash-basis (diakui saat lunas/settled) + net of return, via RewardService.
        $rewardTotals = \App\Services\RewardService::yearTotals($storeId ?: null, $currentYear);
        $rewardToko   = $rewardTotals['store_reward'];
        $rewardOwner  = $rewardTotals['owner_reward'];

        // ── Additional Financial Calculations ────────────────
        $totalExpense = Expense::when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereRaw("DATE_FORMAT(expense_date, '%Y-%m') = ?", [$thisMonth])
            ->sum('amount');

        $todayExpense = Expense::when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereDate('expense_date', $today)
            ->sum('amount');

        $todayProfit = $todaySales - $todayExpense;

        $approachingDueSales = Sale::with(['store'])
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->where('payment_status', '!=', 'lunas')
            ->whereNotNull('due_date')
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        $warehouseId = $request->query('warehouse_id');

        $latestOpname = \App\Models\StockOpname::with(['items.variant.product', 'items.variant.color', 'items.variant.size', 'creator'])
            ->when($storeId, fn($q) => $q->where('location_type', 'store')->where('location_id', $storeId))
            ->when($warehouseId, fn($q) => $q->where('location_type', 'warehouse')->where('location_id', $warehouseId))
            ->latest()
            ->first();

        // Mengembalikan View aslinya (Super Admin Dashboard)
        return view('dashboard.index', compact(
            'stores', 'storeId', 'storeDateFilter', 'topDateFilter',
            'stats',
            'todaySales', 'yesterdaySales', 'monthSales', 'lastMonthSales',
            'todayOrders', 'monthOrders',
            'topProducts',
            'chartLabels', 'chartRevenue', 'chartOrders',
            'salesByStore', 'paymentDistribution',
            'storeStockValue', 'warehouseStockValue',
            'monthReturns', 'pendingReturns',
            // PASTIKAN VARIABEL INI DITAMBAHKAN KE DALAM COMPACT:
            'totalItemsSold', 'rewardToko', 'rewardOwner', 'totalExpense', 'todayExpense', 'todayProfit',
            'approachingDueSales', 'latestOpname'
        ));
    }
}