<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\BrandController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\ColorController;
use App\Http\Controllers\Master\PaymentMethodController;
use App\Http\Controllers\Master\ProductTypeController;
use App\Http\Controllers\Master\ReturnReasonController;
use App\Http\Controllers\Master\SizeController;
use App\Http\Controllers\Master\StoreController;
use App\Http\Controllers\Master\WarehouseController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Root
Route::get('/', fn() => redirect()->route('dashboard'));

// Authenticated routes
Route::middleware(['auth', 'active.user'])->group(function () {

    // Dashboard
    Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
        
        // 1. Kasir biarkan tetap di-redirect langsung ke mesin POS
        if (auth()->user()->hasRole('kasir')) {
            return redirect()->route('pos.session.index');
        }

        // 2. KUNCI PERUBAHAN: 
        // Hapus blok redirect untuk 'admin gudang' dan 'kepala toko'.
        // Biarkan semua role (selain kasir) masuk ke DashboardController 
        // agar sistem bisa memproses tampilan dashboard khusus mereka.
        return app(DashboardController::class)->index($request);

    })->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Catalog (semua user authenticated bisa akses)
    Route::get('/catalog', [App\Http\Controllers\CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/{productVariant}', [App\Http\Controllers\CatalogController::class, 'show'])->name('catalog.show');

    // Expenses (membuat laporan pengeluaran untuk pembelian barang, biaya operasional, dll)
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', [App\Http\Controllers\Expense\ExpenseController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Expense\ExpenseController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Expense\ExpenseController::class, 'store'])->name('store');
        Route::get('/{expense}/edit', [App\Http\Controllers\Expense\ExpenseController::class, 'edit'])->name('edit'); // BARU
        Route::put('/{expense}', [App\Http\Controllers\Expense\ExpenseController::class, 'update'])->name('update'); // BARU
        Route::delete('/{expense}', [App\Http\Controllers\Expense\ExpenseController::class, 'destroy'])->name('destroy'); // BARU
        Route::get('/{expense}', [App\Http\Controllers\Expense\ExpenseController::class, 'show'])->name('show');
    });

    // Master Data
    Route::prefix('master')->name('master.')->group(function () {
        Route::resource('brands', BrandController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('product-types', ProductTypeController::class)->except(['show']);
        Route::resource('colors', ColorController::class)->except(['show']);
        Route::post('sizes/reorder', [SizeController::class, 'reorder'])->name('sizes.reorder');
        Route::resource('sizes', SizeController::class)->except(['show']);
        Route::resource('warehouses', WarehouseController::class)->except(['show']);
        Route::resource('stores', StoreController::class)->except(['show']);
        Route::resource('payment-methods', PaymentMethodController::class)->except(['show']);
        Route::resource('return-reasons', ReturnReasonController::class)->except(['show']);
    });

    // Products & SKU
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [App\Http\Controllers\Product\ProductController::class, 'index'])->name('index');
        Route::get('/export-catalog', [App\Http\Controllers\Product\ProductController::class, 'catalogExport'])->name('catalog-export');
        Route::get('/create', [App\Http\Controllers\Product\ProductController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Product\ProductController::class, 'store'])->name('store');
        
        // Local Stock Entry (Manual)
        Route::get('/stock-entry', [App\Http\Controllers\Product\StockEntryController::class, 'create'])->name('stock-entry.create');
        Route::post('/stock-entry', [App\Http\Controllers\Product\StockEntryController::class, 'store'])->name('stock-entry.store');
        Route::get('/stock-entry/search', [App\Http\Controllers\Product\StockEntryController::class, 'searchVariants'])->name('stock-entry.search');
        
        // Stock Edit (Permission-based)
        Route::get('/stock-edit', [App\Http\Controllers\Product\StockEditController::class, 'index'])->name('stock-edit.index');
        Route::post('/stock-edit', [App\Http\Controllers\Product\StockEditController::class, 'update'])->name('stock-edit.update');
        
        Route::get('/{product}', [App\Http\Controllers\Product\ProductController::class, 'show'])->name('show');
        Route::get('/{product}/edit', [App\Http\Controllers\Product\ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [App\Http\Controllers\Product\ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [App\Http\Controllers\Product\ProductController::class, 'destroy'])->name('destroy');
        Route::get('/{product}/variants/create', [App\Http\Controllers\Product\ProductVariantController::class, 'create'])->name('variants.create');
        Route::post('/{product}/variants', [App\Http\Controllers\Product\ProductVariantController::class, 'store'])->name('variants.store');
        Route::get('/variants/{variant}/edit', [App\Http\Controllers\Product\ProductVariantController::class, 'edit'])->name('variants.edit');
        Route::put('/variants/{variant}', [App\Http\Controllers\Product\ProductVariantController::class, 'update'])->name('variants.update');
        Route::delete('/variants/{variant}', [App\Http\Controllers\Product\ProductVariantController::class, 'destroy'])->name('variants.destroy');
    });

    // Warehouse
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/stock', [App\Http\Controllers\Warehouse\WarehouseStockController::class, 'index'])->name('stock.index');
        Route::get('/inbound', [App\Http\Controllers\Warehouse\InboundController::class, 'index'])->name('inbound.index');
        Route::get('/inbound/create', [App\Http\Controllers\Warehouse\InboundController::class, 'create'])->name('inbound.create');
        Route::post('/inbound', [App\Http\Controllers\Warehouse\InboundController::class, 'store'])->name('inbound.store');
        Route::get('/inbound/{inbound}', [App\Http\Controllers\Warehouse\InboundController::class, 'show'])->name('inbound.show');
        Route::get('/outbound', [App\Http\Controllers\Warehouse\OutboundController::class, 'index'])->name('outbound.index');
        Route::get('/shipments', [App\Http\Controllers\Warehouse\ShipmentController::class, 'index'])->name('shipments.index');
        Route::get('/shipments/create', [App\Http\Controllers\Warehouse\ShipmentController::class, 'create'])->name('shipments.create');
        Route::post('/shipments', [App\Http\Controllers\Warehouse\ShipmentController::class, 'store'])->name('shipments.store');
        Route::get('/shipments/{shipment}', [App\Http\Controllers\Warehouse\ShipmentController::class, 'show'])->name('shipments.show');
        Route::post('/shipments/{shipment}/status', [App\Http\Controllers\Warehouse\ShipmentController::class, 'updateStatus'])->name('shipments.status');
        Route::get('/shipments/{shipment}/print', [App\Http\Controllers\Warehouse\ShipmentController::class, 'printDoc'])->name('shipments.print');
        Route::get('/monitor', [App\Http\Controllers\Warehouse\MonitorController::class, 'index'])->name('monitor');
    });

    // Store
    Route::prefix('store')->name('store.')->group(function () {
        Route::get('/stock', [App\Http\Controllers\Store\StoreStockController::class, 'index'])->name('stock.index');
        Route::get('/receiving', [App\Http\Controllers\Store\ReceivingController::class, 'index'])->name('receiving.index');
        Route::get('/receiving/{shipment}', [App\Http\Controllers\Store\ReceivingController::class, 'show'])->name('receiving.show');
        Route::post('/receiving/{shipment}', [App\Http\Controllers\Store\ReceivingController::class, 'confirm'])->name('receiving.confirm');
        Route::get('/opname', [App\Http\Controllers\Store\StoreOpnameController::class, 'index'])->name('opname.index');
        Route::get('/customers', [App\Http\Controllers\Store\StoreCustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/detail', [App\Http\Controllers\Store\StoreCustomerController::class, 'show'])->name('customers.show');
    });

    // Store-to-Store Transfers
    Route::prefix('transfers')->name('transfers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Transfer\TransferController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Transfer\TransferController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Transfer\TransferController::class, 'store'])->name('store');
        Route::get('/{transfer}', [App\Http\Controllers\Transfer\TransferController::class, 'show'])->name('show');
        Route::post('/{transfer}/approve', [App\Http\Controllers\Transfer\TransferController::class, 'approve'])->name('approve');
        Route::post('/{transfer}/reject', [App\Http\Controllers\Transfer\TransferController::class, 'reject'])->name('reject');
        Route::post('/{transfer}/ship', [App\Http\Controllers\Transfer\TransferController::class, 'ship'])->name('ship');
        Route::post('/{transfer}/receive', [App\Http\Controllers\Transfer\TransferController::class, 'receive'])->name('receive');
        Route::get('/{transfer}/print', [App\Http\Controllers\Transfer\TransferController::class, 'printDoc'])->name('print');
    });

    // POS
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [App\Http\Controllers\POS\POSController::class, 'index'])->name('index');
        Route::get('/session', [App\Http\Controllers\POS\CashSessionController::class, 'index'])->name('session.index');
        Route::post('/session/open', [App\Http\Controllers\POS\CashSessionController::class, 'open'])->name('session.open');
        Route::post('/session/close', [App\Http\Controllers\POS\CashSessionController::class, 'close'])->name('session.close');
        Route::post('/sale', [App\Http\Controllers\POS\POSController::class, 'processSale'])->name('sale');
        Route::get('/sale/{sale}/receipt', [App\Http\Controllers\POS\POSController::class, 'receipt'])->name('receipt');
        Route::get('/history', [App\Http\Controllers\POS\POSController::class, 'history'])->name('history');
        Route::get('/search-product', [App\Http\Controllers\POS\POSController::class, 'searchProduct'])->name('search-product');
        Route::get('/report/export', [App\Http\Controllers\POS\POSController::class, 'exportReport'])->name('report.export');
        Route::get('/customers/autocomplete', [App\Http\Controllers\POS\POSController::class, 'autocompleteCustomer'])->name('customers.autocomplete');
    });

    // Returns
    Route::prefix('returns')->name('returns.')->group(function () {
        // Customer returns
        Route::get('/customer/search-sale', [App\Http\Controllers\Returns\CustomerReturnController::class, 'searchSale'])->name('customer.search-sale');
        Route::get('/customer/search-sales', [App\Http\Controllers\Returns\CustomerReturnController::class, 'searchSales'])->name('customer.search-sales');
        Route::get('/customer', [App\Http\Controllers\Returns\CustomerReturnController::class, 'index'])->name('customer.index');
        Route::get('/customer/create', [App\Http\Controllers\Returns\CustomerReturnController::class, 'create'])->name('customer.create');
        Route::post('/customer', [App\Http\Controllers\Returns\CustomerReturnController::class, 'store'])->name('customer.store');
        Route::get('/customer/{return}', [App\Http\Controllers\Returns\CustomerReturnController::class, 'show'])->name('customer.show');
        // Store returns
        Route::get('/store', [App\Http\Controllers\Returns\StoreReturnController::class, 'index'])->name('store.index');
        Route::get('/store/create', [App\Http\Controllers\Returns\StoreReturnController::class, 'create'])->name('store.create');
        Route::post('/store', [App\Http\Controllers\Returns\StoreReturnController::class, 'store'])->name('store.store');
        Route::get('/store/{return}', [App\Http\Controllers\Returns\StoreReturnController::class, 'show'])->name('store.show');
        Route::post('/store/{return}/receive', [App\Http\Controllers\Returns\StoreReturnController::class, 'receive'])->name('store.receive');
        Route::post('/store/{return}/inspect', [App\Http\Controllers\Returns\StoreReturnController::class, 'inspect'])->name('store.inspect');
    });

    // Stock Opname
    Route::prefix('opname')->name('opname.')->group(function () {
        Route::get('/', [App\Http\Controllers\Opname\StockOpnameController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Opname\StockOpnameController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Opname\StockOpnameController::class, 'store'])->name('store');
        Route::get('/{opname}', [App\Http\Controllers\Opname\StockOpnameController::class, 'show'])->name('show');
        Route::post('/{opname}/submit', [App\Http\Controllers\Opname\StockOpnameController::class, 'submit'])->name('submit');
        Route::post('/{opname}/approve', [App\Http\Controllers\Opname\StockOpnameController::class, 'approve'])->name('approve');
        Route::delete('/{opname}', [App\Http\Controllers\Opname\StockOpnameController::class, 'destroy'])->name('destroy');
    });

    // Finance
    Route::middleware('can:view finance')->prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [App\Http\Controllers\Finance\FinanceController::class, 'index'])->name('index');
        Route::get('/stock-value', [App\Http\Controllers\Finance\FinanceController::class, 'stockValue'])->name('stock-value');
        Route::get('/sales', [App\Http\Controllers\Finance\FinanceController::class, 'sales'])->name('sales');
        Route::get('/rewards', [App\Http\Controllers\Finance\FinanceController::class, 'rewards'])->name('rewards');
        Route::post('/bonus/pay', [App\Http\Controllers\Finance\FinanceController::class, 'payBonus'])->name('bonus.pay');
        Route::get('/export', [App\Http\Controllers\Finance\FinanceController::class, 'export'])->name('export');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\Report\ReportController::class, 'index'])->name('index');
        Route::get('/stock', [App\Http\Controllers\Report\ReportController::class, 'stock'])->name('stock');
        Route::get('/sales', [App\Http\Controllers\Report\ReportController::class, 'sales'])->name('sales');
        Route::get('/sales/{sale}/detail', [App\Http\Controllers\Report\ReportController::class, 'saleDetail'])->name('sale-detail');
        Route::get('/shipment', [App\Http\Controllers\Report\ReportController::class, 'shipment'])->name('shipment');
        Route::get('/transfer', [App\Http\Controllers\Report\ReportController::class, 'transfer'])->name('transfer');
        Route::get('/inbound', [App\Http\Controllers\Report\ReportController::class, 'inbound'])->name('inbound');
        Route::get('/return', [App\Http\Controllers\Report\ReportController::class, 'return'])->name('return');
        Route::get('/export-pdf', [App\Http\Controllers\Report\ReportController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/export-excel', [App\Http\Controllers\Report\ReportController::class, 'exportExcel'])->name('export-excel');
        Route::get('/expenses', [App\Http\Controllers\Report\ReportController::class, 'expenses'])->name('expenses');

    });

    // Exports (PDF / Excel / CSV)
    Route::prefix('exports')->name('exports.')->group(function () {
        Route::get('/sales/pdf/{filename?}',        [App\Http\Controllers\Export\ExportController::class, 'salesPdf'])->name('sales.pdf');
        Route::get('/sales/excel/{filename?}',      [App\Http\Controllers\Export\ExportController::class, 'salesExcel'])->name('sales.excel');
        Route::get('/sales/csv/{filename?}',        [App\Http\Controllers\Export\ExportController::class, 'salesCsv'])->name('sales.csv');
        Route::get('/stock/pdf/{filename?}',        [App\Http\Controllers\Export\ExportController::class, 'stockPdf'])->name('stock.pdf');
        Route::get('/stock/excel/{filename?}',      [App\Http\Controllers\Export\ExportController::class, 'stockExcel'])->name('stock.excel');
        Route::get('/stock/csv/{filename?}',        [App\Http\Controllers\Export\ExportController::class, 'stockCsv'])->name('stock.csv');
        Route::get('/expenses/pdf/{filename?}',     [App\Http\Controllers\Export\ExportController::class, 'expensesPdf'])->name('expenses.pdf');
        Route::get('/expenses/excel/{filename?}',   [App\Http\Controllers\Export\ExportController::class, 'expensesExcel'])->name('expenses.excel');
        Route::get('/shipment/pdf/{filename?}',     [App\Http\Controllers\Export\ExportController::class, 'shipmentPdf'])->name('shipment.pdf');
        Route::get('/shipment/excel/{filename?}',   [App\Http\Controllers\Export\ExportController::class, 'shipmentExcel'])->name('shipment.excel');
        Route::get('/transfer/pdf/{filename?}',     [App\Http\Controllers\Export\ExportController::class, 'transferPdf'])->name('transfer.pdf');
        Route::get('/transfer/excel/{filename?}',   [App\Http\Controllers\Export\ExportController::class, 'transferExcel'])->name('transfer.excel');
        Route::get('/rewards/pdf/{filename?}',      [App\Http\Controllers\Export\ExportController::class, 'rewardsPdf'])->name('rewards.pdf');
        Route::get('/rewards/excel/{filename?}',    [App\Http\Controllers\Export\ExportController::class, 'rewardsExcel'])->name('rewards.excel');
        Route::get('/inbound/pdf/{filename?}',      [App\Http\Controllers\Export\ExportController::class, 'inboundPdf'])->name('inbound.pdf');
        Route::get('/inbound/excel/{filename?}',    [App\Http\Controllers\Export\ExportController::class, 'inboundExcel'])->name('inbound.excel');
    });

    // Label / Tag Printer
    Route::prefix('labels')->name('labels.')->group(function () {
        Route::get('/', [App\Http\Controllers\Label\LabelController::class, 'picker'])->name('picker');
        Route::post('/bulk', [App\Http\Controllers\Label\LabelController::class, 'bulk'])->name('bulk');
        Route::get('/{variant}', [App\Http\Controllers\Label\LabelController::class, 'single'])->name('single');
    });

    // Customers (manajemen pelanggan + kredit)
    Route::post('customers/{customer}/loyalty', [App\Http\Controllers\Customer\CustomerController::class, 'loyalty'])->name('customers.loyalty');
    Route::resource('customers', App\Http\Controllers\Customer\CustomerController::class);

    // Pelunasan / pembayaran nota kredit
    Route::middleware('can:record payment')->group(function () {
        Route::get('/sales/{sale}/payments/create', [App\Http\Controllers\Sale\SalePaymentController::class, 'create'])->name('sales.payments.create');
        Route::post('/sales/{sale}/payments', [App\Http\Controllers\Sale\SalePaymentController::class, 'store'])->name('sales.payments.store');
    });

    // Settlement Toko → Owner
    Route::middleware('can:view settlement')->prefix('settlements')->name('settlements.')->group(function () {
        Route::get('/', [App\Http\Controllers\Finance\SettlementController::class, 'index'])->name('index');
        Route::get('/{store}', [App\Http\Controllers\Finance\SettlementController::class, 'show'])->name('show');
        Route::post('/{store}', [App\Http\Controllers\Finance\SettlementController::class, 'store'])->middleware('can:manage settlement')->name('store');
    });

    // Persetujuan kredit (transaksi tempo yang melebihi batas, mode approval)
    Route::middleware('can:approve credit')->prefix('credit-approvals')->name('credit-approvals.')->group(function () {
        Route::get('/', [App\Http\Controllers\Finance\CreditApprovalController::class, 'index'])->name('index');
        Route::post('/{sale}/approve', [App\Http\Controllers\Finance\CreditApprovalController::class, 'approve'])->name('approve');
        Route::post('/{sale}/reject', [App\Http\Controllers\Finance\CreditApprovalController::class, 'reject'])->name('reject');
    });

    // Settings (kredit global, dll)
    Route::middleware('can:manage settings')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/credit', [App\Http\Controllers\SettingController::class, 'credit'])->name('credit');
        Route::put('/credit', [App\Http\Controllers\SettingController::class, 'updateCredit'])->name('credit.update');
    });

    // Administration
    Route::prefix('admin')->name('admin.')->middleware('can:manage users')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    // AJAX / API endpoints
    Route::prefix('api/v1')->name('api.')->group(function () {
        Route::get('/products/search', [App\Http\Controllers\POS\POSController::class, 'searchProduct'])->name('products.search');
        Route::get('/variants/search', [App\Http\Controllers\Warehouse\InboundController::class, 'searchVariants'])->name('variants.search');
        Route::get('/shipments/{shipment}/status', [App\Http\Controllers\Warehouse\ShipmentController::class, 'getStatus'])->name('shipments.status');
        Route::get('/stock/poll', [App\Http\Controllers\Warehouse\MonitorController::class, 'poll'])->name('stock.poll');
        Route::post('/cast/trigger', [App\Http\Controllers\Admin\CastController::class, 'triggerCast'])->name('cast.trigger');
        Route::get('/cast/check', [App\Http\Controllers\Admin\CastController::class, 'checkCast'])->name('cast.check');
    });

});

require __DIR__ . '/auth.php';
