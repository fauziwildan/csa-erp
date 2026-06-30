<?php

namespace App\Http\Controllers\Export;

use App\Exports\ExpensesExport;
use App\Exports\RewardsExport;
use App\Exports\SalesExport;
use App\Exports\ShipmentExport;
use App\Exports\StockExport;
use App\Exports\TransferExport;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Inbound;
use App\Models\Sale;
use App\Models\Shipment;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\Store;
use App\Models\Transfer;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // LAPORAN PENJUALAN
    // ─────────────────────────────────────────────────────────────

    public function salesPdf(Request $request, $filename = null)
    {
        $this->authorize('export report');

        $user = auth()->user();
        $isGlobal = $user->hasGlobalFinanceAccess() || $user->hasRole('superadmin') || $user->hasRole('owner');

        $query = Sale::with([
            'store',
            'paymentMethod',
            'items.variant' => fn($q) => $q->withTrashed(),
            'items.variant.product' => fn($q) => $q->withTrashed(),
            'items.variant.color',
            'items.variant.size'
        ])
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to));

        if (!$isGlobal) {
            $storeIds = $user->stores()->pluck('stores.id')->toArray();
            if (empty($storeIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('store_id', $storeIds);
            }
        }

        $sales = $query->orderBy('created_at', 'desc')->limit(500)->get();
        $totalSales = $sales->sum('total_amount');
        $totalOrders = $sales->count();
        $store = $request->store_id ? Store::find($request->store_id) : null;

        $pdf = Pdf::loadView('exports.pdf.sales', compact('sales', 'totalSales', 'totalOrders', 'store', 'request'))
            ->setPaper('a4', 'landscape');

        return $this->downloadPdfSecure($pdf, $filename ?? ('laporan-penjualan-' . now()->format('Y-m-d_H-i-s') . '.pdf'));
    }

    public function salesExcel(Request $request, $filename = null)
    {
        $this->authorize('export report');

        $export = new SalesExport($request->store_id, $request->date_from, $request->date_to);
        $filename = $filename ?? ('laporan-penjualan-' . now()->format('Y-m-d_H-i-s') . '.xlsx');

        return $this->downloadExcelSecure($export, $filename);
    }

    public function salesCsv(Request $request, $filename = null)
    {
        $this->authorize('export report');

        $user = auth()->user();
        $isGlobal = $user->hasGlobalFinanceAccess() || $user->hasRole('superadmin') || $user->hasRole('owner');

        $query = Sale::with([
            'store',
            'paymentMethod',
            'items.variant' => fn($q) => $q->withTrashed(),
            'items.variant.product' => fn($q) => $q->withTrashed(),
            'creator'
        ])
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to));

        if (!$isGlobal) {
            $storeIds = $user->stores()->pluck('stores.id')->toArray();
            if (empty($storeIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('store_id', $storeIds);
            }
        }

        $sales = $query->orderBy('created_at', 'desc')->get();
        $filename = $filename ?? ('laporan-penjualan-' . now()->format('Y-m-d_H-i-s') . '.csv');

        // Header yang kompatibel dengan Bluefy (iOS WebKit)
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];

        $callback = function () use ($sales) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 agar Excel / Numbers membaca encoding dengan benar
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'No. Penjualan',
                'Toko',
                'Metode Bayar',
                'Kasir',
                'Total Transaksi',
                'Tanggal',
                'Item',
                'SKU/Variant',
                'Qty',
                'Harga Satuan',
                'Subtotal Item',
            ]);

            foreach ($sales as $s) {
                foreach ($s->items as $idx => $item) {
                    if ($idx === 0) {
                        fputcsv($handle, [
                            $s->sale_no,
                            $s->store->name,
                            $s->paymentMethod?->name ?? '-',
                            $s->creator?->name ?? '-',
                            $s->total_amount,
                            $s->created_at->format('d/m/Y H:i'),
                            $item->variant?->product?->name ?? 'Produk Terhapus',
                            $item->variant?->sku ?? '-',
                            $item->qty,
                            $item->unit_price,
                            $item->subtotal,
                        ]);
                    } else {
                        fputcsv($handle, [
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            $item->variant?->product?->name ?? 'Produk Terhapus',
                            $item->variant?->sku ?? '-',
                            $item->qty,
                            $item->unit_price,
                            $item->subtotal,
                        ]);
                    }
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─────────────────────────────────────────────────────────────
    // LAPORAN STOK
    // ─────────────────────────────────────────────────────────────

    public function stockPdf(Request $request, $filename = null)
    {
        $this->authorize('export report');
        $user = auth()->user();

        // Ambil data lokasi yang diizinkan untuk user ini
        if ($user->hasRole(['superadmin', 'owner', 'finance'])) {
            $locationType = $request->location_type ?? 'warehouse';
            $locationId = $request->location_id;
        } elseif ($user->hasRole('admin gudang')) {
            $locationType = 'warehouse';
            $warehouses = $user->warehouses()->pluck('warehouses.id');
            $locationId = $request->location_id ?? $warehouses->first();
            if ($request->location_id && !$warehouses->contains($request->location_id)) {
                $locationId = $warehouses->first();
            }
        } elseif ($user->hasRole('kepala toko')) {
            $locationType = 'store';
            $stores = $user->stores()->pluck('stores.id');
            $locationId = $request->location_id ?? $stores->first();
            if ($request->location_id && !$stores->contains($request->location_id)) {
                $locationId = $stores->first();
            }
        } else {
            abort(403, 'Unauthorized location access.');
        }

        $searchProduct = $request->search_product;
        $brandId = $request->brand_id;
        $colorId = $request->color_id;
        $sizeId = $request->size_id;

        $stocks = Stock::with([
            'variant' => fn($q) => $q->withTrashed(),
            'variant.product' => fn($q) => $q->withTrashed(),
            'variant.product.brand',
            'variant.color',
            'variant.size'
        ])
            ->join('product_variants as pv', 'stocks.product_variant_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->where('stocks.location_type', $locationType)
            ->when($locationId, fn($q) => $q->where('stocks.location_id', $locationId))
            ->when($searchProduct, fn($q) => $q->where('p.name', 'like', '%' . $searchProduct . '%'))
            ->when($brandId, fn($q) => $q->where('p.brand_id', $brandId))
            ->when($colorId, fn($q) => $q->where('pv.color_id', $colorId))
            ->when($sizeId, fn($q) => $q->where('pv.size_id', $sizeId))
            ->where('stocks.qty', '>', 0)
            ->select('stocks.*')
            ->orderByDesc('stocks.qty')
            ->limit(1000)
            ->get();

        $totalQty = $stocks->sum('qty');
        $location = $locationId
            ? ($locationType === 'warehouse' ? Warehouse::find($locationId) : Store::find($locationId))
            : null;

        // Label filter untuk ditampilkan di header PDF
        $filterBrand = $brandId ? \App\Models\Brand::find($brandId)?->name : null;
        $filterColor = $colorId ? \App\Models\Color::find($colorId)?->name : null;
        $filterSize = $sizeId ? \App\Models\Size::find($sizeId)?->name : null;

        $pdf = Pdf::loadView('exports.pdf.stock', compact(
            'stocks',
            'totalQty',
            'location',
            'locationType',
            'searchProduct',
            'filterBrand',
            'filterColor',
            'filterSize'
        ))->setPaper('a4', 'portrait');

        return $this->downloadPdfSecure($pdf, $filename ?? ('laporan-stok-' . now()->format('Y-m-d_H-i-s') . '.pdf'));
    }

    public function stockExcel(Request $request, $filename = null)
    {
        $this->authorize('export report');
        $user = auth()->user();

        // Logika role-based yang sama untuk Excel
        if ($user->hasRole(['superadmin', 'owner', 'finance'])) {
            $locationType = $request->location_type ?? 'warehouse';
            $locationId = $request->location_id;
        } elseif ($user->hasRole('admin gudang')) {
            $locationType = 'warehouse';
            $warehouses = $user->warehouses()->pluck('warehouses.id');
            $locationId = $request->location_id ?? $warehouses->first();
        } elseif ($user->hasRole('kepala toko')) {
            $locationType = 'store';
            $stores = $user->stores()->pluck('stores.id');
            $locationId = $request->location_id ?? $stores->first();
        } else {
            abort(403);
        }

        $export = new StockExport(
            $locationType,
            $locationId ? (int) $locationId : null,
            $request->search_product,
            $request->brand_id ? (int) $request->brand_id : null,
            $request->color_id ? (int) $request->color_id : null,
            $request->size_id ? (int) $request->size_id : null,
        );
        $filename = $filename ?? ('laporan-stok-' . now()->format('Y-m-d_H-i-s') . '.xlsx');

        return $this->downloadExcelSecure($export, $filename);
    }

    public function stockCsv(Request $request, $filename = null)
    {
        $this->authorize('export report');
        $user = auth()->user();

        // Logika role-based yang sama untuk CSV
        if ($user->hasRole(['superadmin', 'owner', 'finance'])) {
            $locationType = $request->location_type ?? 'warehouse';
            $locationId = $request->location_id;
        } elseif ($user->hasRole('admin gudang')) {
            $locationType = 'warehouse';
            $locationId = $request->location_id ?? $user->warehouses()->first()?->id;
        } elseif ($user->hasRole('kepala toko')) {
            $locationType = 'store';
            $locationId = $request->location_id ?? $user->stores()->first()?->id;
        } else {
            abort(403);
        }

        $stocks = Stock::with([
            'variant' => fn($q) => $q->withTrashed(),
            'variant.product' => fn($q) => $q->withTrashed(),
            'variant.product.brand',
            'variant.color',
            'variant.size'
        ])
            ->where('location_type', $locationType)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->where('qty', '>', 0)
            ->orderByDesc('qty')
            ->get();

        $filename = $filename ?? ('laporan-stok-' . now()->format('Y-m-d_H-i-s') . '.csv');
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($stocks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SKU', 'Produk', 'Brand', 'Warna', 'Ukuran', 'Tipe Lokasi', 'ID Lokasi', 'Qty']);
            foreach ($stocks as $s) {
                fputcsv($handle, [
                    $s->variant?->sku ?? '-',
                    $s->variant?->product?->name ?? 'Produk Terhapus',
                    $s->variant?->product?->brand?->name ?? '-',
                    $s->variant?->color?->name ?? '-',
                    $s->variant?->size?->name ?? '-',
                    $s->location_type,
                    $s->location_id,
                    $s->qty,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─────────────────────────────────────────────────────────────
    // LAPORAN PENGELUARAN
    // ─────────────────────────────────────────────────────────────

    public function expensesPdf(Request $request, $filename = null)
    {
        $this->authorize('export report');

        $user = auth()->user();
        $query = Expense::with(['store', 'warehouse', 'creator']);

        if ($user->hasAnyRole(['superadmin', 'owner'])) {
            if ($request->filled('source_filter')) {
                $source = explode('_', $request->source_filter);
                if ($source[0] === 'store') {
                    $query->where('store_id', $source[1]);
                } elseif ($source[0] === 'warehouse') {
                    $query->where('warehouse_id', $source[1]);
                }
            }
        } elseif ($user->hasRole('kepala toko')) {
            $storeIds = $user->stores()->pluck('stores.id');
            $query->whereIn('store_id', $storeIds);
        } elseif ($user->hasRole('admin gudang')) {
            $warehouseIds = $user->warehouses()->pluck('warehouses.id');
            $query->whereIn('warehouse_id', $warehouseIds);
        } else {
            abort(403);
        }

        if ($request->filled('expense_type')) {
            $query->where('expense_type', $request->expense_type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        $expenses = $query->latest('expense_date')->limit(500)->get();
        $totalAmount = $expenses->sum('amount');

        // Build source label for display
        $sourceFilter = null;
        if ($request->filled('source_filter')) {
            $parts = explode('_', $request->source_filter, 2);
            if ($parts[0] === 'store') {
                $s = Store::find($parts[1]);
                $sourceFilter = 'Toko: ' . ($s?->name ?? $parts[1]);
            } elseif ($parts[0] === 'warehouse') {
                $w = Warehouse::find($parts[1]);
                $sourceFilter = 'Gudang: ' . ($w?->name ?? $parts[1]);
            }
        }

        $expenseType = $request->expense_type;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        $pdf = Pdf::loadView('exports.pdf.expenses', compact(
            'expenses',
            'totalAmount',
            'sourceFilter',
            'expenseType',
            'dateFrom',
            'dateTo'
        ))->setPaper('a4', 'portrait');

        return $this->downloadPdfSecure($pdf, $filename ?? ('laporan-pengeluaran-' . now()->format('Y-m-d_H-i-s') . '.pdf'));
    }

    public function expensesExcel(Request $request, $filename = null)
    {
        $this->authorize('export report');

        $export = new ExpensesExport(
            $request->source_filter,
            $request->expense_type,
            $request->date_from,
            $request->date_to
        );
        $filename = $filename ?? ('laporan-pengeluaran-' . now()->format('Y-m-d_H-i-s') . '.xlsx');

        return $this->downloadExcelSecure($export, $filename);
    }

    // ─────────────────────────────────────────────────────────────
    // LAPORAN PENGIRIMAN
    // ─────────────────────────────────────────────────────────────

    public function shipmentPdf(Request $request, $filename = null)
    {
        $this->authorize('export report');
        $user = auth()->user();

        // Enforce role-based filtering
        if ($user->hasRole(['superadmin', 'owner', 'finance'])) {
            $warehouseId = $request->warehouse_id;
            $storeId = $request->store_id;
        } elseif ($user->hasRole('admin gudang')) {
            $warehouses = $user->warehouses()->pluck('warehouses.id');
            $warehouseId = $request->warehouse_id ?? $warehouses->first();
            $storeId = $request->store_id;
        } elseif ($user->hasRole('kepala toko')) {
            $warehouseId = $request->warehouse_id;
            $stores = $user->stores()->pluck('stores.id');
            $storeId = $request->store_id ?? $stores->first();
            if ($request->store_id && !$stores->contains($request->store_id)) {
                $storeId = $stores->first();
            }
        } else {
            abort(403);
        }

        $shipments = Shipment::with(['warehouse', 'store', 'items'])
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get();

        $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;
        $store = $storeId ? Store::find($storeId) : null;

        $pdf = Pdf::loadView('exports.pdf.shipment', compact('shipments', 'warehouse', 'store', 'request'))
            ->setPaper('a4', 'portrait');

        return $this->downloadPdfSecure($pdf, $filename ?? ('laporan-pengiriman-' . now()->format('Y-m-d_H-i-s') . '.pdf'));
    }

    public function shipmentExcel(Request $request, $filename = null)
    {
        $this->authorize('export report');
        $user = auth()->user();

        // Enforce role-based filtering
        if ($user->hasRole(['superadmin', 'owner', 'finance'])) {
            $warehouseId = $request->warehouse_id;
            $storeId = $request->store_id;
        } elseif ($user->hasRole('admin gudang')) {
            $warehouses = $user->warehouses()->pluck('warehouses.id');
            $warehouseId = $request->warehouse_id ?? $warehouses->first();
            $storeId = $request->store_id;
        } elseif ($user->hasRole('kepala toko')) {
            $warehouseId = $request->warehouse_id;
            $stores = $user->stores()->pluck('stores.id');
            $storeId = $request->store_id ?? $stores->first();
        } else {
            abort(403);
        }

        $export = new ShipmentExport(
            $warehouseId,
            $storeId,
            $request->status,
            $request->date_from,
            $request->date_to
        );
        $filename = $filename ?? ('laporan-pengiriman-' . now()->format('Y-m-d_H-i-s') . '.xlsx');

        return $this->downloadExcelSecure($export, $filename);
    }

    public function inboundPdf(Request $request, $filename = null)
    {
        $this->authorize('export report');
        $user = auth()->user();

        if ($user->hasRole(['superadmin', 'owner', 'finance'])) {
            $warehouseId = $request->warehouse_id;
        } elseif ($user->hasRole('admin gudang')) {
            $warehouses = $user->warehouses()->pluck('warehouses.id');
            $warehouseId = $request->warehouse_id ?? $warehouses->first();
            if ($request->warehouse_id && !$warehouses->contains($request->warehouse_id)) {
                $warehouseId = $warehouses->first();
            }
        } else {
            abort(403);
        }

        $inbounds = Inbound::with(['warehouse', 'creator', 'items'])
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get();

        $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;

        $pdf = Pdf::loadView('exports.pdf.inbound', compact('inbounds', 'warehouse', 'request'))
            ->setPaper('a4', 'portrait');

        return $this->downloadPdfSecure($pdf, $filename ?? ('laporan-barang-masuk-' . now()->format('Y-m-d_H-i-s') . '.pdf'));
    }

    public function inboundExcel(Request $request, $filename = null)
    {
        $this->authorize('export report');
        $user = auth()->user();

        if ($user->hasRole(['superadmin', 'owner', 'finance'])) {
            $warehouseId = $request->warehouse_id;
        } elseif ($user->hasRole('admin gudang')) {
            $warehouses = $user->warehouses()->pluck('warehouses.id');
            $warehouseId = $request->warehouse_id ?? $warehouses->first();
        } else {
            abort(403);
        }

        $export = new \App\Exports\InboundExport(
            $warehouseId,
            $request->status,
            $request->date_from,
            $request->date_to
        );
        $filename = $filename ?? ('laporan-barang-masuk-' . now()->format('Y-m-d_H-i-s') . '.xlsx');

        return $this->downloadExcelSecure($export, $filename);
    }

    // ─────────────────────────────────────────────────────────────
    // LAPORAN TRANSFER TOKO
    // ─────────────────────────────────────────────────────────────

    public function transferPdf(Request $request, $filename = null)
    {
        $this->authorize('export report');

        $transfers = Transfer::with(['fromStore', 'toStore', 'items'])
            ->when($request->from_store_id, fn($q) => $q->where('from_store_id', $request->from_store_id))
            ->when($request->to_store_id, fn($q) => $q->where('to_store_id', $request->to_store_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get();

        $fromStore = $request->from_store_id ? Store::find($request->from_store_id) : null;
        $toStore = $request->to_store_id ? Store::find($request->to_store_id) : null;

        $pdf = Pdf::loadView('exports.pdf.transfer', compact('transfers', 'fromStore', 'toStore', 'request'))
            ->setPaper('a4', 'portrait');

        return $this->downloadPdfSecure($pdf, $filename ?? ('laporan-transfer-toko-' . now()->format('Y-m-d_H-i-s') . '.pdf'));
    }

    public function transferExcel(Request $request, $filename = null)
    {
        $this->authorize('export report');

        $export = new TransferExport(
            $request->from_store_id,
            $request->to_store_id,
            $request->status,
            $request->date_from,
            $request->date_to
        );
        $filename = $filename ?? ('laporan-transfer-toko-' . now()->format('Y-m-d_H-i-s') . '.xlsx');

        return $this->downloadExcelSecure($export, $filename);
    }

    // ─────────────────────────────────────────────────────────────
    // LAPORAN REWARD & BONUS TOKO
    // ─────────────────────────────────────────────────────────────

    public function rewardsPdf(Request $request, $filename = null)
    {
        $this->authorize('view finance');

        $user = auth()->user();
        $isGlobal = $user->hasGlobalFinanceAccess();

        $month = $request->input('month', now()->format('m'));
        $year = $request->input('year', now()->format('Y'));
        $stores = $isGlobal ? Store::orderBy('name')->get() : $user->stores()->orderBy('name')->get();

        $storeRewards = [];
        foreach ($stores as $store) {
            $storeRewards[] = \App\Services\RewardService::storeMonthly($store, (int) $month, (int) $year);
        }

        $pdf = Pdf::loadView('exports.pdf.rewards', compact('storeRewards', 'month', 'year'))
            ->setPaper('a4', 'portrait');

        return $this->downloadPdfSecure($pdf, $filename ?? ('laporan-reward-bonus-' . now()->format('Y-m-d_H-i-s') . '.pdf'));
    }

    public function rewardsExcel(Request $request, $filename = null)
    {
        $this->authorize('view finance');

        $user = auth()->user();
        $isGlobal = $user->hasGlobalFinanceAccess();

        $month = $request->input('month', now()->format('m'));
        $year = $request->input('year', now()->format('Y'));
        $stores = $isGlobal ? Store::orderBy('name')->get() : $user->stores()->orderBy('name')->get();

        $storeRewards = [];
        foreach ($stores as $store) {
            $storeRewards[] = \App\Services\RewardService::storeMonthly($store, (int) $month, (int) $year);
        }

        $filename = $filename ?? ('laporan-reward-bonus-' . now()->format('Y-m-d_H-i-s') . '.xlsx');

        return $this->downloadExcelSecure(new RewardsExport($storeRewards), $filename);
    }

    /**
     * Helper to download Excel file with strict headers for iOS/Bluefy
     */
    protected function downloadExcelSecure($export, string $filename)
    {
        $diskPath = 'exports/' . $filename;
        $fullPath = storage_path('app/' . $diskPath);

        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        Excel::store($export, $diskPath, 'local');

        if (!file_exists($fullPath)) {
            return Excel::download($export, $filename);
        }

        $fileSize = filesize($fullPath);

        // Instantiate BinaryFileResponse
        $response = response()->download($fullPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Length' => $fileSize,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);

        // Crucial override: strips out Symfony's automatic filename* parameter
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    /**
     * Helper to download PDF file with strict headers for iOS/Bluefy
     */
    protected function downloadPdfSecure($pdf, string $filename)
    {
        $content = $pdf->output();
        
        // Return standard response with clean Content-Disposition
        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

