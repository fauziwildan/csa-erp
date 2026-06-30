<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Inbound;
use App\Models\Sale;
use App\Models\Shipment;
use App\Models\Stock;
use App\Models\Store;
use App\Models\Transfer;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index()
    {
        $this->authorize('view report');
        return view('reports.index');
    }

    public function sales(Request $r)
    {
        $this->authorize('view report');
        $user   = Auth::user();
        $stores = $user->hasAnyRole(['superadmin', 'owner', 'finance']) ? Store::orderBy('name')->get() : collect();

        $q = Sale::with(['store', 'paymentMethod', 'items', 'payments.paymentMethod'])
            ->when($r->store_id,  fn($q) => $q->where('store_id', $r->store_id))
            ->when($r->date_from, fn($q) => $q->whereDate('created_at', '>=', $r->date_from))
            ->when($r->date_to,   fn($q) => $q->whereDate('created_at', '<=', $r->date_to))
            ->orderBy('created_at', 'desc');

        if (!$user->hasAnyRole(['superadmin', 'owner', 'finance'])) {
            $storeIds = $user->stores->pluck('id');
            $q->whereIn('store_id', $storeIds);
        }

        $sales       = $q->paginate(30)->withQueryString();
        $totalSales  = $q->toBase()->sum('total_amount');
        $totalOrders = $q->toBase()->count();

        return view('reports.sales', compact('sales', 'stores', 'totalSales', 'totalOrders'));
    }

    public function stock(Request $r)
    {
        $this->authorize('view report');
        $user = Auth::user();
        
        if ($user->hasRole(['superadmin', 'owner', 'finance'])) {
            $warehouses = Warehouse::orderBy('name')->get();
            $stores     = Store::orderBy('name')->get();
        } elseif ($user->hasRole('admin gudang')) {
            $warehouses = $user->warehouses()->orderBy('name')->get();
            $stores     = collect();
        } elseif ($user->hasRole('kepala toko')) {
            $warehouses = collect();
            $stores     = $user->stores()->orderBy('name')->get();
        } else {
            $warehouses = collect();
            $stores     = collect();
        }

        // Enforce location type and location ID based on role
        if ($user->hasRole('kepala toko')) {
            $locationType = 'store';
            $locationId   = $r->location_id ?? $stores->first()?->id;
            if ($r->location_id && !$stores->contains('id', $r->location_id)) {
                $locationId = $stores->first()?->id;
            }
        } elseif ($user->hasRole('admin gudang')) {
            $locationType = 'warehouse';
            $locationId   = $r->location_id ?? $warehouses->first()?->id;
            if ($r->location_id && !$warehouses->contains('id', $r->location_id)) {
                $locationId = $warehouses->first()?->id;
            }
        } else {
            $locationType = $r->location_type ?? 'warehouse';
            $locationId   = $r->location_id;
        }

        // Resolve current location object for display
        $currentLocation = $locationId
            ? ($locationType === 'warehouse' ? $warehouses->firstWhere('id', $locationId) : $stores->firstWhere('id', $locationId))
            : null;

        // Filter data tambahan: produk, brand, warna, ukuran
        $q = Stock::with(['variant.product.brand', 'variant.color', 'variant.size'])
            ->join('product_variants as pv', 'stocks.product_variant_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->where('stocks.qty', '>', 0)
            ->where('stocks.location_type', $locationType)
            ->when($locationId,        fn($q) => $q->where('stocks.location_id', $locationId))
            ->when($r->search_product, fn($q) => $q->where('p.name', 'like', '%' . $r->search_product . '%'))
            ->when($r->brand_id,       fn($q) => $q->where('p.brand_id', $r->brand_id))
            ->when($r->color_id,       fn($q) => $q->where('pv.color_id', $r->color_id))
            ->when($r->size_id,        fn($q) => $q->where('pv.size_id', $r->size_id))
            ->select('stocks.*')
            ->orderBy('stocks.qty', 'desc');

        $stocks   = $q->paginate(50)->withQueryString();
        $totalQty = $q->toBase()->sum('stocks.qty');

        // Data untuk dropdown filter
        $brands = \App\Models\Brand::orderBy('name')->get();
        $colors = \App\Models\Color::orderBy('name')->get();
        $sizes  = \App\Models\Size::orderBy('name')->get();

        return view('reports.stock', compact(
            'stocks', 'warehouses', 'stores', 'locationType', 'locationId',
            'currentLocation', 'totalQty', 'brands', 'colors', 'sizes'
        ));
    }

    public function shipment(Request $r)
    {
        $this->authorize('view report');
        $user = auth()->user();

        // Ambil list gudang & toko sesuai role
        if ($user->hasRole(['superadmin', 'owner', 'finance'])) {
            $warehouses = Warehouse::orderBy('name')->get();
            $stores     = Store::orderBy('name')->get();
        } elseif ($user->hasRole('admin gudang')) {
            $warehouses = $user->warehouses()->orderBy('name')->get();
            $stores     = Store::orderBy('name')->get();
        } elseif ($user->hasRole('kepala toko')) {
            $warehouses = Warehouse::orderBy('name')->get();
            $stores     = $user->stores()->orderBy('name')->get();
        } else {
            $warehouses = collect();
            $stores     = collect();
        }

        // Tentukan warehouseId aktif (Filter Asal)
        $warehouseId = $r->warehouse_id;
        if ($user->hasRole('admin gudang')) {
            $warehouseId = $r->warehouse_id ?? $warehouses->first()?->id;
            if ($r->warehouse_id && !$warehouses->contains('id', $r->warehouse_id)) {
                $warehouseId = $warehouses->first()?->id;
            }
        }
        $currentWarehouse = $warehouseId ? $warehouses->firstWhere('id', $warehouseId) : null;

        // Tentukan storeId aktif (Filter Tujuan)
        $storeId = $r->store_id;
        if ($user->hasRole('kepala toko')) {
            $storeId = $r->store_id ?? $stores->first()?->id;
            if ($r->store_id && !$stores->contains('id', $r->store_id)) {
                $storeId = $stores->first()?->id;
            }
        }
        $currentStore = $storeId ? $stores->firstWhere('id', $storeId) : null;

        $shipments = Shipment::with(['warehouse', 'store', 'items'])
            ->when($warehouseId,  fn($q) => $q->where('warehouse_id', $warehouseId))
            ->when($storeId,      fn($q) => $q->where('store_id', $storeId))
            ->when($r->status,       fn($q) => $q->where('status', $r->status))
            ->when($r->date_from,    fn($q) => $q->whereDate('created_at', '>=', $r->date_from))
            ->when($r->date_to,      fn($q) => $q->whereDate('created_at', '<=', $r->date_to))
            ->orderBy('created_at', 'desc')
            ->paginate(30)->withQueryString();

        return view('reports.shipment', compact('shipments', 'warehouses', 'stores', 'warehouseId', 'storeId', 'currentWarehouse', 'currentStore'));
    }

    public function inbound(Request $r)
    {
        $this->authorize('view report');
        $user = auth()->user();

        if ($user->hasRole(['superadmin', 'owner', 'finance'])) {
            $warehouses = Warehouse::orderBy('name')->get();
        } elseif ($user->hasRole('admin gudang')) {
            $warehouses = $user->warehouses()->orderBy('name')->get();
        } else {
            $warehouses = collect();
        }

        $warehouseId = $r->warehouse_id;
        if ($user->hasRole('admin gudang')) {
            $warehouseId = $r->warehouse_id ?? $warehouses->first()?->id;
            if ($r->warehouse_id && !$warehouses->contains('id', $r->warehouse_id)) {
                $warehouseId = $warehouses->first()?->id;
            }
        }

        $currentWarehouse = $warehouseId ? $warehouses->firstWhere('id', $warehouseId) : null;

        $inbounds = Inbound::with(['warehouse', 'creator', 'items'])
            ->when($warehouseId,  fn($q) => $q->where('warehouse_id', $warehouseId))
            ->when($r->status,       fn($q) => $q->where('status', $r->status))
            ->when($r->date_from,    fn($q) => $q->whereDate('created_at', '>=', $r->date_from))
            ->when($r->date_to,      fn($q) => $q->whereDate('created_at', '<=', $r->date_to))
            ->orderBy('created_at', 'desc')
            ->paginate(30)->withQueryString();

        return view('reports.inbound', compact('inbounds', 'warehouses', 'warehouseId', 'currentWarehouse'));
    }

    public function transfer(Request $r)
    {
        $this->authorize('view report');
        $stores = Store::orderBy('name')->get();

        $transfers = Transfer::with(['fromStore', 'toStore', 'items'])
            ->when($r->from_store_id, fn($q) => $q->where('from_store_id', $r->from_store_id))
            ->when($r->to_store_id,   fn($q) => $q->where('to_store_id', $r->to_store_id))
            ->when($r->status,        fn($q) => $q->where('status', $r->status))
            ->when($r->date_from,     fn($q) => $q->whereDate('created_at', '>=', $r->date_from))
            ->when($r->date_to,       fn($q) => $q->whereDate('created_at', '<=', $r->date_to))
            ->orderBy('created_at', 'desc')
            ->paginate(30)->withQueryString();

        return view('reports.transfer', compact('transfers', 'stores'));
    }

    public function expenses(Request $request)
    {
        $user = auth()->user();
        $query = \App\Models\Expense::with(['store', 'warehouse', 'creator']);

        // 1. Filter Akses Role (Wajib, tidak bisa di-override user)
        if ($user->hasAnyRole(['superadmin', 'owner'])) {
            if ($request->filled('source_filter')) {
                $source = explode('_', $request->source_filter); 
                if ($source[0] === 'store') {
                    $query->where('store_id', $source[1]);
                } elseif ($source[0] === 'warehouse') {
                    $query->where('warehouse_id', $source[1]);
                }
            }
        } 
        elseif ($user->hasRole('kepala toko')) {
            // FIX BUG: Ambil array ID Toko dari relasi stores()
            $storeIds = $user->stores()->pluck('stores.id');
            $query->whereIn('store_id', $storeIds);
        } 
        elseif ($user->hasRole('admin gudang')) {
            $warehouseIds = $user->warehouses()->pluck('warehouses.id');
            $query->whereIn('warehouse_id', $warehouseIds);
        } else {
            abort(403, 'Akses ditolak.');
        }

        // 2. Filter Jenis Pengeluaran
        if ($request->filled('expense_type')) {
            $query->where('expense_type', $request->expense_type);
        }

        // 3. Filter Tanggal (Dari - Sampai)
        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        // 4. Hitung Summary (Total Keseluruhan berdasarkan filter, BUKAN cuma yang tampil di 1 halaman)
        $totalAmount = $query->sum('amount');
        $totalTransactions = $query->count();

        // 5. Eksekusi query dengan pagination
        $expenses = $query->latest('expense_date')->paginate(20)->withQueryString();

        return view('reports.expenses', compact('expenses', 'totalAmount', 'totalTransactions'));
    }

    public function return(Request $r)
    {
        $this->authorize('view report');
        return redirect()->route('reports.sales');
    }

    /**
     * AJAX endpoint untuk detail penjualan di halaman laporan.
     * Menggunakan permission 'view report' agar bisa diakses owner/finance, bukan hanya kasir.
     */
    public function saleDetail(Sale $sale)
    {
        // Bisa diakses oleh siapa saja yang punya 'view report' atau 'view pos'
        if (! (auth()->user()->can('view report') || auth()->user()->can('view pos'))) {
            abort(403);
        }

        $sale->load([
            'store',
            'paymentMethod',
            'payments.paymentMethod',
            'creator',
            'items.variant.product',
            'items.variant.color',
            'items.variant.size',
        ]);

        $receiptHtml = null;
        try {
            $receiptHtml = view('pos.partials.receipt_html', compact('sale'))->render();
        } catch (\Throwable $e) {
            // Library QR/barcode mungkin tidak tersedia di server hosting ini
            // Fallback: kembalikan data saja tanpa HTML preview
            \Illuminate\Support\Facades\Log::warning('receipt_html render failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'sale'    => $sale,
            'html'    => $receiptHtml,
        ]);
    }

    public function exportPdf()   { return back()->with('warning', 'Export PDF belum tersedia.'); }
    public function exportExcel() { return back()->with('warning', 'Export Excel belum tersedia.'); }
}
