<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\MovementDateController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreManagementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\FundTransferController;
use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\AccountingPeriodController;
use App\Http\Controllers\PurchasingDashboardController;
use App\Http\Controllers\FinanceDashboardController;
use App\Http\Controllers\AccountingDashboardController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\ApprovalWorkflowController;
use App\Http\Controllers\StorePerformanceReportController;

// Fallback: serve files from the public disk (e.g. attendance selfies) via Laravel
// when the public/storage symlink is missing/broken (it is only hit if no static file matches).
Route::get('/storage/{path}', function (string $path) {
    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    abort_if(str_contains($path, '..') || !$disk->exists($path), 404);

    return $disk->response($path, null, ['Cache-Control' => 'public, max-age=86400']);
})->where('path', '.*')->name('storage.fallback');

// Dedicated Loyalty System Routes
Route::get('/loyalty', [\App\Http\Controllers\LoyaltyCardController::class, 'index'])->name('loyalty.index');
Route::get('/card/{token}', [\App\Http\Controllers\LoyaltyCardController::class, 'show'])->name('loyalty.card');
Route::post('/customers/{customer}/action', [\App\Http\Controllers\LoyaltyCardController::class, 'quickAction'])->name('loyalty.action');
Route::post('/loyalty/tiers', [\App\Http\Controllers\LoyaltyCardController::class, 'updateTiers'])->name('loyalty.tiers.update');
Route::get('/api/loyalty/search', [\App\Http\Controllers\LoyaltyCardController::class, 'search'])->name('loyalty.search');
Route::post('/api/loyalty/register', [\App\Http\Controllers\LoyaltyCardController::class, 'registerFast'])->name('loyalty.register');

// 1. Guest Routes (Login Screen)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// 2. Auth-Protected Routes
Route::middleware('auth')->group(function () {
    // Logout Action
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Workspace Portal (App Launcher)
    Route::get('/portal', [\App\Http\Controllers\PortalController::class, 'index'])->name('portal');

    // Store Selection
    Route::get('/select-store', [StoreController::class, 'showSelectStore'])->name('select-store');
    Route::post('/select-store', [StoreController::class, 'selectStore']);
    Route::post('/switch-store', [StoreController::class, 'switchStore'])->name('switch-store');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Modular Specialized Dashboards & Modules
    Route::prefix('purchasing')->name('purchasing.')->group(function () {
        Route::get('/dashboard', [PurchasingDashboardController::class, 'index'])->name('dashboard');

        // 1. Order Barang (Permintaan Restock Cabang)
        Route::get('/orders', [\App\Http\Controllers\PurchasingOrderController::class, 'index'])->name('orders.index');
        Route::post('/orders', [\App\Http\Controllers\PurchasingOrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{id}', [\App\Http\Controllers\PurchasingOrderController::class, 'show'])->name('orders.show');
        Route::delete('/orders/{id}', [\App\Http\Controllers\PurchasingOrderController::class, 'destroy'])->name('orders.destroy');
        Route::post('/orders/{id}/items', [\App\Http\Controllers\PurchasingOrderController::class, 'addItem'])->name('orders.add-item');
        Route::delete('/orders/items/{id}', [\App\Http\Controllers\PurchasingOrderController::class, 'destroyItem'])->name('orders.destroy-item');
        Route::post('/orders/{id}/status', [\App\Http\Controllers\PurchasingOrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::get('/orders/{id}/print', [\App\Http\Controllers\PurchasingOrderController::class, 'print'])->name('orders.print');

        // 2. Purchase Order (Surat Pesanan Resmi ke Supplier - Kelanjutan Order Barang)
        Route::get('/purchase-orders', [\App\Http\Controllers\PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('/purchase-orders/create', [\App\Http\Controllers\PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('/purchase-orders', [\App\Http\Controllers\PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('/purchase-orders/{id}', [\App\Http\Controllers\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::post('/purchase-orders/{id}/items', [\App\Http\Controllers\PurchaseOrderController::class, 'addItem'])->name('purchase-orders.items.add');
        Route::put('/purchase-orders/{id}/items/{itemId}', [\App\Http\Controllers\PurchaseOrderController::class, 'updateItem'])->name('purchase-orders.items.update');
        Route::post('/purchase-orders/{id}/items/{itemId}/toggle-cancel', [\App\Http\Controllers\PurchaseOrderController::class, 'toggleCancelItem'])->name('purchase-orders.items.toggle-cancel');
        Route::delete('/purchase-orders/{id}/items/{itemId}', [\App\Http\Controllers\PurchaseOrderController::class, 'deleteItem'])->name('purchase-orders.items.delete');
        Route::post('/purchase-orders/{id}/status', [\App\Http\Controllers\PurchaseOrderController::class, 'updateStatus'])->name('purchase-orders.update-status');
        Route::post('/purchase-orders/{id}/receive', [\App\Http\Controllers\PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
        Route::get('/purchase-orders/{id}/print', [\App\Http\Controllers\PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
        Route::delete('/purchase-orders/{id}', [\App\Http\Controllers\PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
    });
    Route::get('/finance/dashboard', [FinanceDashboardController::class, 'index'])->name('finance.dashboard');
    Route::get('/accounting/dashboard', [AccountingDashboardController::class, 'index'])->name('accounting.dashboard');

    // Commercial: Admin Kehadiran & Master Data Shift
    Route::get('/human-resources', [\App\Http\Controllers\HumanResourceController::class, 'index']);
    Route::prefix('commercial')->name('commercial.')->group(function () {
        // Commercial: Human Resources Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\CommercialHrDashboardController::class, 'index'])->name('hr.dashboard');

        // Commercial: Human Resources Hub
        Route::get('/human-resources', [\App\Http\Controllers\HumanResourceController::class, 'index'])->name('hr');

        // Commercial: Monitoring CCTV
        Route::get('/monitoring', [\App\Http\Controllers\CommercialMonitoringController::class, 'index'])->name('monitoring.index');
        Route::post('/monitoring/cameras', [\App\Http\Controllers\CommercialMonitoringController::class, 'storeCamera'])->name('monitoring.cameras.store');
        Route::put('/monitoring/cameras/{id}', [\App\Http\Controllers\CommercialMonitoringController::class, 'updateCamera'])->name('monitoring.cameras.update');
        Route::delete('/monitoring/cameras/{id}', [\App\Http\Controllers\CommercialMonitoringController::class, 'destroyCamera'])->name('monitoring.cameras.destroy');

        Route::get('/attendances/export', [\App\Http\Controllers\CommercialAttendanceController::class, 'export'])->name('attendances.export');
        Route::post('/attendances/import', [\App\Http\Controllers\CommercialAttendanceController::class, 'import'])->name('attendances.import');
        Route::get('/attendances', [\App\Http\Controllers\CommercialAttendanceController::class, 'index'])->name('attendances.index');
        Route::get('/attendances/{user}', [\App\Http\Controllers\CommercialAttendanceController::class, 'show'])->name('attendances.show');
        Route::post('/attendances/{user}/record', [\App\Http\Controllers\CommercialAttendanceController::class, 'saveRecord'])->name('attendances.save-record');

        // Commercial: Payroll Employee
        Route::get('/payrolls', [\App\Http\Controllers\CommercialPayrollController::class, 'index'])->name('payrolls.index');
        Route::get('/payrolls/settings', [\App\Http\Controllers\CommercialPayrollController::class, 'settings'])->name('payrolls.settings');
        Route::post('/payrolls/settings', [\App\Http\Controllers\CommercialPayrollController::class, 'updateSettings'])->name('payrolls.settings.update');
        Route::post('/payrolls/generate', [\App\Http\Controllers\CommercialPayrollController::class, 'generate'])->name('payrolls.generate');
        Route::get('/payrolls/{payroll}', [\App\Http\Controllers\CommercialPayrollController::class, 'show'])->name('payrolls.show');
        Route::put('/payrolls/{payroll}', [\App\Http\Controllers\CommercialPayrollController::class, 'update'])->name('payrolls.update');
        Route::post('/payrolls/{payroll}/status', [\App\Http\Controllers\CommercialPayrollController::class, 'updateStatus'])->name('payrolls.status');
        Route::delete('/payrolls/{payroll}', [\App\Http\Controllers\CommercialPayrollController::class, 'destroy'])->name('payrolls.destroy');

        // Master Data Shift: 1. Shift
        Route::get('/shifts', [\App\Http\Controllers\CommercialShiftController::class, 'index'])->name('shifts.index');
        Route::post('/shifts', [\App\Http\Controllers\CommercialShiftController::class, 'store'])->name('shifts.store');
        Route::put('/shifts/{shift}', [\App\Http\Controllers\CommercialShiftController::class, 'update'])->name('shifts.update');
        Route::delete('/shifts/{shift}', [\App\Http\Controllers\CommercialShiftController::class, 'destroy'])->name('shifts.destroy');
        Route::post('/shifts/{shift}/toggle', [\App\Http\Controllers\CommercialShiftController::class, 'toggleStatus'])->name('shifts.toggle');

        // Master Data Shift: 2. Attendance Status
        Route::get('/attendance-statuses', [\App\Http\Controllers\CommercialAttendanceStatusController::class, 'index'])->name('attendance-statuses.index');
        Route::post('/attendance-statuses', [\App\Http\Controllers\CommercialAttendanceStatusController::class, 'store'])->name('attendance-statuses.store');
        Route::put('/attendance-statuses/{attendanceStatus}', [\App\Http\Controllers\CommercialAttendanceStatusController::class, 'update'])->name('attendance-statuses.update');
        Route::delete('/attendance-statuses/{attendanceStatus}', [\App\Http\Controllers\CommercialAttendanceStatusController::class, 'destroy'])->name('attendance-statuses.destroy');
        Route::post('/attendance-statuses/{attendanceStatus}/toggle', [\App\Http\Controllers\CommercialAttendanceStatusController::class, 'toggleStatus'])->name('attendance-statuses.toggle');

        // Master Data Shift: 3. User Schedule
        Route::get('/schedules', [\App\Http\Controllers\CommercialUserScheduleController::class, 'index'])->name('schedules.index');
        Route::post('/schedules', [\App\Http\Controllers\CommercialUserScheduleController::class, 'store'])->name('schedules.store');
        Route::post('/schedules/generate', [\App\Http\Controllers\CommercialUserScheduleController::class, 'bulkGenerate'])->name('schedules.generate');
        Route::delete('/schedules', [\App\Http\Controllers\CommercialUserScheduleController::class, 'destroy'])->name('schedules.destroy');

        // Master Data Shift: 4. Shift Pattern
        Route::get('/shift-patterns', [\App\Http\Controllers\CommercialShiftPatternController::class, 'index'])->name('shift-patterns.index');
        Route::post('/shift-patterns', [\App\Http\Controllers\CommercialShiftPatternController::class, 'store'])->name('shift-patterns.store');
        Route::put('/shift-patterns/{shiftPattern}', [\App\Http\Controllers\CommercialShiftPatternController::class, 'update'])->name('shift-patterns.update');
        Route::delete('/shift-patterns/{shiftPattern}', [\App\Http\Controllers\CommercialShiftPatternController::class, 'destroy'])->name('shift-patterns.destroy');
        Route::post('/shift-patterns/{shiftPattern}/toggle', [\App\Http\Controllers\CommercialShiftPatternController::class, 'toggleStatus'])->name('shift-patterns.toggle');
    });

    // Z1 App: Portal Karyawan (Absensi, Purchase Request, Report Staff)
    Route::get('/z1-app', [\App\Http\Controllers\Z1AppController::class, 'index'])->name('z1-app.index');
    Route::post('/z1-app/clock-in', [\App\Http\Controllers\Z1AppController::class, 'clockIn'])->name('z1-app.clock-in');
    Route::post('/z1-app/clock-out', [\App\Http\Controllers\Z1AppController::class, 'clockOut'])->name('z1-app.clock-out');
    Route::post('/z1-app/purchase-request', [\App\Http\Controllers\Z1AppController::class, 'storePurchaseRequest'])->name('z1-app.purchase-request.store');
    Route::get('/z1-app/purchase-request/{id}', [\App\Http\Controllers\Z1AppController::class, 'showPurchaseRequest'])->name('z1-app.purchase-request.show');
    Route::post('/z1-app/purchase-request/{id}/items', [\App\Http\Controllers\Z1AppController::class, 'addPurchaseRequestItem'])->name('z1-app.purchase-request.add-item');
    Route::delete('/z1-app/purchase-request-items/{id}', [\App\Http\Controllers\Z1AppController::class, 'destroyPurchaseRequestItem'])->name('z1-app.purchase-request.destroy-item');
    Route::delete('/z1-app/purchase-request/{id}', [\App\Http\Controllers\Z1AppController::class, 'destroyPurchaseRequest'])->name('z1-app.purchase-request.destroy');

    // User Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Inventory List (Products)
    Route::get('/stock-card', [ProductController::class, 'stockCardIndex'])->name('stock-card.index');
    Route::get('/inventory/{product}/stock-card', [ProductController::class, 'stockCard'])->name('inventory.stock-card');
    Route::get('/inventory/{product}/stock-card/export', [ProductController::class, 'exportStockCard'])->name('inventory.stock-card.export');
    Route::post('/inventory/bulk-destroy', [ProductController::class, 'bulkDestroy'])->name('inventory.bulk-destroy');
    Route::delete('/inventory/bulk-destroy', [ProductController::class, 'bulkDestroy']);
    Route::get('/inventory/export', [ProductController::class, 'export'])->name('inventory.export');
    Route::get('/inventory/import/template', [ProductController::class, 'importTemplate'])->name('inventory.import.template');
    Route::get('/inventory/import', [ProductController::class, 'importView'])->name('inventory.import.view');
    Route::post('/inventory/import', [ProductController::class, 'import'])->name('inventory.import');
    Route::resource('inventory', ProductController::class)->parameters([
        'inventory' => 'product'
    ]);

    // Stock Movements
    Route::post('/movements/bulk-approve', [StockMovementController::class, 'bulkApprove'])->name('movements.bulk-approve');
    Route::post('/movements/bulk-unapprove', [StockMovementController::class, 'bulkUnapprove'])->name('movements.bulk-unapprove');
    Route::post('/movements/bulk-destroy', [StockMovementController::class, 'bulkDestroy'])->name('movements.bulk-destroy');
    Route::delete('/movements/bulk-destroy', [StockMovementController::class, 'bulkDestroy']);
    Route::get('/movements', [StockMovementController::class, 'index'])->name('movements.index');
    Route::get('/movements/create', [StockMovementController::class, 'create'])->name('movements.create');
    Route::post('/movements', [StockMovementController::class, 'store'])->name('movements.store');
    Route::match(['put', 'patch'], '/movements/{id}', [StockMovementController::class, 'update'])->name('movements.update');
    Route::post('/movements/{id}/approve', [StockMovementController::class, 'approve'])->name('movements.approve');
    Route::post('/movements/{id}/unapprove', [StockMovementController::class, 'unapprove'])->name('movements.unapprove');
    Route::delete('/movements/{id}', [StockMovementController::class, 'destroy'])->name('movements.destroy');

    // Immutable stock audit trail; corrections are reversed, never deleted.
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::post('/stock-adjustments/session-date', [StockAdjustmentController::class, 'storeDate'])->name('stock-adjustments.store-date');
    Route::post('/stock-adjustments/assign-payroll', [StockAdjustmentController::class, 'assignPayroll'])->name('stock-adjustments.assign-payroll');
    Route::get('/stock-adjustments/date/{date}', [StockAdjustmentController::class, 'showByDate'])->name('stock-adjustments.date');
    Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
    Route::get('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show');
    Route::post('/stock-adjustments/{stockAdjustment}/reverse', [StockAdjustmentController::class, 'reverse'])->name('stock-adjustments.reverse');

    // Multi-Store Stock Transfers (Mutasi Barang Antar Cabang / Surat Jalan)
    Route::get('/stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
    Route::get('/stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
    Route::post('/stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');
    Route::get('/stock-transfers/{id}', [StockTransferController::class, 'show'])->name('stock-transfers.show');
    Route::post('/stock-transfers/{id}/send', [StockTransferController::class, 'send'])->name('stock-transfers.send');
    Route::post('/stock-transfers/{id}/receive', [StockTransferController::class, 'receive'])->name('stock-transfers.receive');
    Route::post('/stock-transfers/{id}/cancel', [StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');
    Route::get('/stock-transfers/{id}/print', [StockTransferController::class, 'print'])->name('stock-transfers.print');
    Route::delete('/stock-transfers/{id}', [StockTransferController::class, 'destroy'])->name('stock-transfers.destroy');

    // Movement Dates (date group manager)
    Route::post('/movement-dates/bulk-destroy', [MovementDateController::class, 'bulkDestroy'])->name('movement-dates.bulk-destroy');
    Route::delete('/movement-dates/bulk-destroy', [MovementDateController::class, 'bulkDestroy']);
    Route::get('/movement-dates/import-template', [MovementDateController::class, 'importTemplate'])->name('movement-dates.import-template');
    Route::post('/movement-dates', [MovementDateController::class, 'store'])->name('movement-dates.store');
    Route::patch('/movement-dates/{id}', [MovementDateController::class, 'update'])->name('movement-dates.update');
    Route::post('/movement-dates/{id}/import', [MovementDateController::class, 'importProducts'])->name('movement-dates.import');
    Route::get('/movement-dates/{id}', [MovementDateController::class, 'show'])->name('movement-dates.show');
    Route::delete('/movement-dates/{id}', [MovementDateController::class, 'destroy'])->name('movement-dates.destroy');

    // Sales
    Route::post('/sales/bulk-destroy', [SaleController::class, 'bulkDestroy'])->name('sales.bulk-destroy');
    Route::delete('/sales/bulk-destroy', [SaleController::class, 'bulkDestroy']);
    Route::put('/sales/{sale}/cashier', [SaleController::class, 'updateCashier'])->name('sales.update-cashier');
    Route::patch('/sales/{sale}/cashier', [SaleController::class, 'updateCashier']);
    Route::put('/sales/{sale}/payment-method', [SaleController::class, 'updatePaymentMethod'])->name('sales.update-payment-method');
    Route::patch('/sales/{sale}/payment-method', [SaleController::class, 'updatePaymentMethod']);
    Route::put('/sales/{sale}/discount', [SaleController::class, 'updateDiscount'])->name('sales.update-discount');
    Route::patch('/sales/{sale}/discount', [SaleController::class, 'updateDiscount']);
    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show', 'destroy', 'update']);

    // Store Incomes (Pemasukan Toko)
    Route::resource('incomes', IncomeController::class);

    // Store Expenses (Pengeluaran Toko)
    Route::resource('expenses', ExpenseController::class);

    // Finance: Hutang Antar Cabang (Inter-Branch Debts & Settlements)
    Route::prefix('finance/interbranch-debts')->name('finance.interbranch-debts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\InterBranchDebtController::class, 'index'])->name('index');
        Route::post('/loan', [\App\Http\Controllers\InterBranchDebtController::class, 'storeDirectLoan'])->name('store-loan');
        Route::post('/{debt}/pay', [\App\Http\Controllers\InterBranchDebtController::class, 'storePayment'])->name('store-payment');
        Route::delete('/payments/{payment}', [\App\Http\Controllers\InterBranchDebtController::class, 'destroyPayment'])->name('destroy-payment');
        Route::delete('/{debt}', [\App\Http\Controllers\InterBranchDebtController::class, 'destroy'])->name('destroy');
        Route::post('/link-expense/{expense}', [\App\Http\Controllers\InterBranchDebtController::class, 'linkLegacyExpense'])->name('link-expense');
        Route::get('/store-accounts/{store}', [\App\Http\Controllers\InterBranchDebtController::class, 'getStorePaymentAccounts'])->name('store-accounts');
    });

    // Master Data: Users
    Route::resource('users', UserController::class);

    // Master Data: Stores (CRUD)
    Route::resource('stores', StoreManagementController::class);

    // Master Data: Roles & Permissions
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');

    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    // Master Data: Settings
    Route::resource('settings', SettingController::class)->only(['index', 'store', 'update', 'destroy']);

    // Master Data: Approval Workflows
    Route::get('/master-data/approval-workflows', [ApprovalWorkflowController::class, 'index'])->name('master-data.approval-workflows.index');
    Route::put('/master-data/approval-workflows', [ApprovalWorkflowController::class, 'update'])->name('master-data.approval-workflows.update');

    // Master Data Hub
    Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data.index');

    // Executive BI: Store Performance Report
    Route::get('/reports/store-performance', [StorePerformanceReportController::class, 'index'])->name('reports.store-performance');
    Route::get('/reports/store-performance/export', [StorePerformanceReportController::class, 'export'])->name('reports.store-performance.export');

    // Reports Hub & Detail Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/by-sku', [ReportController::class, 'bySku'])->name('reports.by-sku');
    Route::get('/reports/by-sku/export', [ReportController::class, 'exportBySku'])->name('reports.by-sku.export');
    Route::get('/reports/by-sku/export-detail', [ReportController::class, 'exportDetail'])->name('reports.by-sku.export-detail');
    Route::get('/reports/by-category', [ReportController::class, 'byCategory'])->name('reports.by-category');
    Route::get('/reports/by-staff', [ReportController::class, 'byStaff'])->name('reports.by-staff');
    Route::get('/reports/top-suppliers', [ReportController::class, 'topSuppliers'])->name('reports.top-suppliers');
    Route::get('/reports/top-suppliers/export', [ReportController::class, 'exportTopSuppliers'])->name('reports.top-suppliers.export');
    Route::get('/reports/low-stock', [ReportController::class, 'lowStock'])->name('reports.low-stock');
    Route::get('/reports/low-stock/export', [ReportController::class, 'exportLowStock'])->name('reports.low-stock.export');

    // Accounting: Chart of Accounts, Journals & General Ledger
    Route::get('/accounting/accounts', [AccountController::class, 'index'])->name('accounting.accounts.index');
    Route::get('/accounting/accounts/create', [AccountController::class, 'create'])->name('accounting.accounts.create');
    Route::post('/accounting/accounts', [AccountController::class, 'store'])->name('accounting.accounts.store');
    Route::get('/accounting/accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounting.accounts.edit');
    Route::put('/accounting/accounts/{account}', [AccountController::class, 'update'])->name('accounting.accounts.update');
    Route::delete('/accounting/accounts/{account}', [AccountController::class, 'destroy'])->name('accounting.accounts.destroy');

    Route::get('/accounting/journals/export', [JournalController::class, 'export'])->name('accounting.journals.export');
    Route::get('/accounting/journals', [JournalController::class, 'index'])->name('accounting.journals.index');
    Route::get('/accounting/journals/create', [JournalController::class, 'create'])->name('accounting.journals.create');
    Route::post('/accounting/journals', [JournalController::class, 'store'])->name('accounting.journals.store');
    Route::get('/accounting/journals/{journal}', [JournalController::class, 'show'])->name('accounting.journals.show');
    Route::get('/accounting/ledger', [JournalController::class, 'ledger'])->name('accounting.journals.ledger');

    // Accounting: Financial Statements / Reports (Phase 3)
    Route::match(['get', 'post'], '/accounting/reports/sync-journals', [FinancialReportController::class, 'syncJournals'])->name('accounting.reports.sync-journals');
    Route::get('/accounting/reports/profit-and-loss', [FinancialReportController::class, 'profitAndLoss'])->name('accounting.reports.profit-and-loss');
    Route::get('/accounting/reports/profit-loss', [FinancialReportController::class, 'profitAndLoss'])->name('accounting.reports.profit-loss');
    Route::get('/accounting/reports/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('accounting.reports.balance-sheet');
    Route::get('/accounting/reports/trial-balance', [FinancialReportController::class, 'trialBalance'])->name('accounting.reports.trial-balance');
    Route::get('/accounting/reports/cash-flow', [FinancialReportController::class, 'cashFlow'])->name('accounting.reports.cash-flow');

    // Accounting: Cash & Bank Transfers (Phase 4)
    Route::get('/accounting/transfers', [FundTransferController::class, 'index'])->name('accounting.transfers.index');
    Route::get('/accounting/transfers/create', [FundTransferController::class, 'create'])->name('accounting.transfers.create');
    Route::post('/accounting/transfers', [FundTransferController::class, 'store'])->name('accounting.transfers.store');
    Route::delete('/accounting/transfers/{transfer}', [FundTransferController::class, 'destroy'])->name('accounting.transfers.destroy');

    // Accounting: Bank & Cash Reconciliation (Penyesuaian Saldo Kas & Bank)
    Route::get('/accounting/reconciliations', [BankReconciliationController::class, 'index'])->name('accounting.reconciliations.index');
    Route::get('/accounting/reconciliations/create', [BankReconciliationController::class, 'create'])->name('accounting.reconciliations.create');
    Route::post('/accounting/reconciliations', [BankReconciliationController::class, 'store'])->name('accounting.reconciliations.store');
    Route::delete('/accounting/reconciliations/{reconciliation}', [BankReconciliationController::class, 'destroy'])->name('accounting.reconciliations.destroy');

    // Accounting: Accounts Payable / Hutang Supplier (Phase 4)
    Route::get('/accounting/payables', [SupplierPaymentController::class, 'index'])->name('accounting.payables.index');
    Route::get('/accounting/payables/batch-pay', [SupplierPaymentController::class, 'payBatchForm'])->name('accounting.payables.batch-pay');
    Route::post('/accounting/payables/batch-pay', [SupplierPaymentController::class, 'storeBatchPayment'])->name('accounting.payables.store-batch');
    Route::get('/accounting/payables/{stockMovement}/pay', [SupplierPaymentController::class, 'payForm'])->name('accounting.payables.pay');
    Route::post('/accounting/payables/{stockMovement}/pay', [SupplierPaymentController::class, 'storePayment'])->name('accounting.payables.store');
    Route::delete('/accounting/payables/payments/{supplierPayment}', [SupplierPaymentController::class, 'destroy'])->name('accounting.payables.destroy-payment');

    // Accounting: Legacy Debts (Hutang Warisan Bersama)
    Route::post('/accounting/payables/legacy/{legacyDebt}/pay', [SupplierPaymentController::class, 'storeLegacyPayment'])->name('accounting.payables.legacy.pay');
    Route::delete('/accounting/payables/legacy/payments/{legacyPayment}', [SupplierPaymentController::class, 'destroyLegacyPayment'])->name('accounting.payables.legacy.destroy-payment');

    // Accounting: Period Closing / Tutup Buku (Phase 4)
    Route::get('/accounting/periods', [AccountingPeriodController::class, 'index'])->name('accounting.periods.index');
    Route::post('/accounting/periods', [AccountingPeriodController::class, 'store'])->name('accounting.periods.store');
    Route::patch('/accounting/periods/{period}/reopen', [AccountingPeriodController::class, 'reopen'])->name('accounting.periods.reopen');
    Route::patch('/accounting/periods/{period}/close', [AccountingPeriodController::class, 'close'])->name('accounting.periods.close');
    Route::delete('/accounting/periods/{period}', [AccountingPeriodController::class, 'destroy'])->name('accounting.periods.destroy');
});

// Public API for Website Z1 Integration
Route::get('/api/products', function (\Illuminate\Http\Request $request) {
    $query = \App\Models\Product::withoutGlobalScopes();

    if ($request->has('category') && !empty($request->category)) {
        $query->where('category', $request->category);
    }

    if ($request->has('search') && !empty($request->search)) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    $products = $query->orderBy('name', 'asc')->get();

    return response()->json([
        'status' => 'success',
        'count' => $products->count(),
        'data' => $products
    ]);
});

// POS API Routes for pos_sales Cashier Application
Route::prefix('api/pos')->group(function () {
    Route::get('/stores', [\App\Http\Controllers\Api\PosApiController::class, 'stores']);
    Route::get('/cashiers', [\App\Http\Controllers\Api\PosApiController::class, 'cashiers']);
    Route::post('/login', [\App\Http\Controllers\Api\PosApiController::class, 'login']);
    Route::get('/products', [\App\Http\Controllers\Api\PosApiController::class, 'products']);
    Route::post('/checkout', [\App\Http\Controllers\Api\PosApiController::class, 'checkout']);
    Route::get('/recent-sales', [\App\Http\Controllers\Api\PosApiController::class, 'recentSales']);
    Route::get('/summary-today', [\App\Http\Controllers\Api\PosApiController::class, 'todaySummary']);
    Route::get('/store-balance', [\App\Http\Controllers\Api\PosApiController::class, 'storeBalance']);
    Route::get('/attendance/today', [\App\Http\Controllers\Api\PosApiController::class, 'attendanceToday']);
    Route::get('/attendance/shifts', [\App\Http\Controllers\Api\PosApiController::class, 'attendanceShifts']);
    Route::get('/attendance/history', [\App\Http\Controllers\Api\PosApiController::class, 'attendanceHistory']);
    Route::post('/attendance/clock-in', [\App\Http\Controllers\Api\PosApiController::class, 'attendanceClockIn']);
    Route::post('/attendance/clock-out', [\App\Http\Controllers\Api\PosApiController::class, 'attendanceClockOut']);
    Route::get('/purchase-requests', [\App\Http\Controllers\Api\PosApiController::class, 'purchaseRequests']);
    Route::post('/purchase-requests', [\App\Http\Controllers\Api\PosApiController::class, 'storePurchaseRequest']);
    Route::get('/reports/staff-summary', [\App\Http\Controllers\Api\PosApiController::class, 'staffSalesReport']);
    Route::post('/sales/{id}/cancel', [\App\Http\Controllers\Api\PosApiController::class, 'cancelSale']);
    Route::match(['put', 'patch', 'post'], '/sales/{id}/payment-method', [\App\Http\Controllers\Api\PosApiController::class, 'updatePaymentMethod']);
    Route::match(['put', 'patch', 'post'], '/sales/{id}/discount', [\App\Http\Controllers\Api\PosApiController::class, 'updateDiscount']);
});

Route::get('/run-migration-auto-sync', function () {
    try {
        $phpVersion = phpversion();
        $results = [];

        // Pre-step: Ensure vendor compatibility with PHP 8.3 if hosting runs 8.3
        $symfonyCommandFile = base_path('vendor/symfony/console/Command/Command.php');
        if (file_exists($symfonyCommandFile) && is_writable($symfonyCommandFile)) {
            $cmdContent = file_get_contents($symfonyCommandFile);
            if (str_contains($cmdContent, '(new \ReflectionClass(static::class)->getAttributes(')) {
                $cmdContent = str_replace(
                    '(new \ReflectionClass(static::class)->getAttributes(',
                    '((new \ReflectionClass(static::class))->getAttributes(',
                    $cmdContent
                );
                file_put_contents($symfonyCommandFile, $cmdContent);
                $results[] = 'Patched symfony/console Command.php for PHP 8.3';
            }
        }

        $casterFile = base_path('vendor/symfony/var-dumper/Caster/Caster.php');
        if (file_exists($casterFile) && is_writable($casterFile)) {
            $casterContent = file_get_contents($casterFile);
            if (str_contains($casterContent, '$p->isVirtual()')) {
                $casterContent = str_replace(
                    '$p->isVirtual()',
                    '(method_exists($p, \'isVirtual\') && $p->isVirtual())',
                    $casterContent
                );
                file_put_contents($casterFile, $casterContent);
                $results[] = 'Patched symfony/var-dumper Caster.php for PHP 8.3';
            }
        }

        // 0. Attempt to run artisan migrate --force
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $artisanOutput = trim(\Illuminate\Support\Facades\Artisan::output());
            if ($artisanOutput) {
                $results[] = 'Artisan migrate: ' . $artisanOutput;
            }
        } catch (\Throwable $t) {
            $results[] = 'Artisan migrate note: ' . $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine();
        }

        // 1. Ensure stores table has bank and balance columns
        if (\Illuminate\Support\Facades\Schema::hasTable('stores')) {
            \Illuminate\Support\Facades\Schema::table('stores', function (\Illuminate\Database\Schema\Blueprint $table) use (&$results) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('stores', 'bank_name')) {
                    $table->string('bank_name')->nullable()->after('phone');
                    $results[] = 'Added bank_name to stores';
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('stores', 'bank_account_number')) {
                    $table->string('bank_account_number')->nullable()->after('bank_name');
                    $results[] = 'Added bank_account_number to stores';
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('stores', 'bank_account_name')) {
                    $table->string('bank_account_name')->nullable()->after('bank_account_number');
                    $results[] = 'Added bank_account_name to stores';
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('stores', 'initial_balance')) {
                    $table->decimal('initial_balance', 15, 2)->default(0)->after('bank_account_name');
                    $results[] = 'Added initial_balance to stores';
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('stores', 'initial_balance_date')) {
                    $table->date('initial_balance_date')->nullable()->after('initial_balance');
                    $results[] = 'Added initial_balance_date to stores';
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('stores', 'auto_cash_to_bank')) {
                    $table->boolean('auto_cash_to_bank')->default(false)->after('bank_account_name');
                    $results[] = 'Added auto_cash_to_bank to stores';
                }
            });

            // Auto-enable for any store that already has bank information configured
            \Illuminate\Support\Facades\DB::table('stores')
                ->whereNotNull('bank_name')
                ->where('bank_name', '!=', '')
                ->update(['auto_cash_to_bank' => true]);
        }

        // 2. Ensure stock_movements table has payment_method and status columns
        if (\Illuminate\Support\Facades\Schema::hasTable('stock_movements')) {
            \Illuminate\Support\Facades\Schema::table('stock_movements', function (\Illuminate\Database\Schema\Blueprint $table) use (&$results) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('stock_movements', 'payment_method')) {
                    $table->string('payment_method', 50)->nullable()->after('total_price');
                    $results[] = 'Added payment_method to stock_movements';
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('stock_movements', 'status')) {
                    $table->string('status')->default('approved')->after('notes');
                    $results[] = 'Added status to stock_movements';
                }
            });
        }

        // 2a. Ensure shifts table has clockin and clockout window columns
        if (\Illuminate\Support\Facades\Schema::hasTable('shifts')) {
            \Illuminate\Support\Facades\Schema::table('shifts', function (\Illuminate\Database\Schema\Blueprint $table) use (&$results) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('shifts', 'early_clock_in_minutes')) {
                    $table->integer('early_clock_in_minutes')->default(120)->after('late_tolerance_minutes');
                    $results[] = 'Added early_clock_in_minutes to shifts';
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('shifts', 'max_clock_out_delay_minutes')) {
                    $table->integer('max_clock_out_delay_minutes')->default(240)->after('early_clock_in_minutes');
                    $results[] = 'Added max_clock_out_delay_minutes to shifts';
                }
            });
        }

        // 2b. Ensure sales table has payment_method, payment_splits and status columns
        if (\Illuminate\Support\Facades\Schema::hasTable('sales')) {
            \Illuminate\Support\Facades\Schema::table('sales', function (\Illuminate\Database\Schema\Blueprint $table) use (&$results) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('sales', 'payment_method')) {
                    $table->string('payment_method', 50)->nullable()->default('cash')->after('notes');
                    $results[] = 'Added payment_method to sales';
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('sales', 'payment_splits')) {
                    $table->json('payment_splits')->nullable()->after('payment_method');
                    $results[] = 'Added payment_splits to sales';
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('sales', 'status')) {
                    $table->string('status', 20)->default('completed')->after('payment_method');
                    $results[] = 'Added status to sales';
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('sales', 'cancellation_reason')) {
                    $table->text('cancellation_reason')->nullable();
                    $results[] = 'Added cancellation_reason to sales';
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('sales', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable();
                    $results[] = 'Added cancelled_at to sales';
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('sales', 'cancelled_by')) {
                    $table->unsignedBigInteger('cancelled_by')->nullable();
                    $results[] = 'Added cancelled_by to sales';
                }
            });
        }

        // 2c. Synchronize sales payment_method with stock_movements & notes to resolve discrepancies
        if (\Illuminate\Support\Facades\Schema::hasTable('sales') && \Illuminate\Support\Facades\Schema::hasColumn('sales', 'payment_method')) {
            $syncedSalesCount = 0;
            \Illuminate\Support\Facades\DB::table('sales')->orderBy('id')->chunk(100, function ($sales) use (&$syncedSalesCount) {
                foreach ($sales as $sale) {
                    $correctMethod = null;

                    // A. Check notes for explicit POS receipt payment method
                    if (!empty($sale->notes) && preg_match('/Bayar:\s*([A-Za-z]+)/i', $sale->notes, $matches)) {
                        $correctMethod = strtolower($matches[1]);
                    }

                    // B. Fallback to related stock_movements if notes is missing or didn't provide a non-cash method
                    if (!$correctMethod || $correctMethod === 'cash') {
                        $smMethod = \Illuminate\Support\Facades\DB::table('stock_movements')
                            ->where('reference_number', $sale->reference_number)
                            ->whereNotNull('payment_method')
                            ->where('payment_method', '!=', '')
                            ->value('payment_method');
                        if (!empty($smMethod) && strtolower($smMethod) !== 'cash') {
                            $correctMethod = strtolower($smMethod);
                        }
                    }

                    $finalMethod = $correctMethod ?: ($sale->payment_method ?: 'cash');

                    if ($sale->payment_method !== $finalMethod) {
                        \Illuminate\Support\Facades\DB::table('sales')
                            ->where('id', $sale->id)
                            ->update(['payment_method' => $finalMethod]);
                        $syncedSalesCount++;
                    }

                    // Ensure stock_movements payment_method also matches
                    \Illuminate\Support\Facades\DB::table('stock_movements')
                        ->where('reference_number', $sale->reference_number)
                        ->where(function ($q) use ($finalMethod) {
                            $q->whereNull('payment_method')->orWhere('payment_method', '!=', $finalMethod);
                        })
                        ->update(['payment_method' => $finalMethod]);
                }
            });

            if ($syncedSalesCount > 0) {
                $results[] = "Synchronized {$syncedSalesCount} sales payment_method in database";
            }
        }

        // 2d. Backfill historical Stock Out records to sales & sale_items
        try {
            \Illuminate\Support\Facades\Artisan::call('stock-out:sync-sales');
            $syncSalesOutput = trim(\Illuminate\Support\Facades\Artisan::output());
            if ($syncSalesOutput) {
                $results[] = 'Sync Stock Out: ' . $syncSalesOutput;
            }
        } catch (\Throwable $t) {
            $results[] = 'Sync Stock Out note: ' . $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine();
        }

        // 2e. Re-align created_at timestamps on all Stock Out sales to their movement dates
        try {
            $updatedTimestampsCount = 0;
            \Illuminate\Support\Facades\DB::table('sales')
                ->where('notes', 'LIKE', '[Stock Out - %')
                ->orderBy('id')
                ->chunk(100, function ($sales) use (&$updatedTimestampsCount) {
                    foreach ($sales as $sale) {
                        $movement = \Illuminate\Support\Facades\DB::table('stock_movements')
                            ->where('reference_number', $sale->reference_number)
                            ->first();

                        if ($movement) {
                            $mDate = $movement->movement_date;
                            $mTime = !empty($movement->created_at) ? date('H:i:s', strtotime($movement->created_at)) : '12:00:00';
                            $targetTime = $mDate . ' ' . $mTime;

                            \Illuminate\Support\Facades\DB::table('sales')
                                ->where('id', $sale->id)
                                ->update([
                                    'created_at' => $targetTime,
                                    'updated_at' => $targetTime,
                                ]);

                            \Illuminate\Support\Facades\DB::table('sale_items')
                                ->where('sale_id', $sale->id)
                                ->update([
                                    'created_at' => $targetTime,
                                    'updated_at' => $targetTime,
                                ]);

                            $updatedTimestampsCount++;
                        }
                    }
                });

            if ($updatedTimestampsCount > 0) {
                $results[] = "Re-aligned created_at timestamps for {$updatedTimestampsCount} Stock Out sales";
            }
        } catch (\Throwable $t) {
            $results[] = 'Timestamp re-align note: ' . $t->getMessage();
        }

        // 2f. Sync cancelled sales & purge journals for voided/cancelled/orphan sales
        try {
            $purgedCount = 0;
            $accountingService = app(\App\Services\AccountingService::class);

            $hasOutMovements = \Illuminate\Support\Facades\DB::table('stock_movements')->where('type', 'out')->exists();
            $activeOutMovementRefs = collect();

            if ($hasOutMovements) {
                $activeOutMovementRefs = \Illuminate\Support\Facades\DB::table('stock_movements')
                    ->where('type', 'out')
                    ->where(function ($sub) {
                        $sub->whereNull('status')
                            ->orWhereNotIn('status', ['cancelled', 'canceled', 'batal', 'void']);
                    })
                    ->where(function ($sub) {
                        $sub->whereNull('notes')
                            ->orWhere(function ($n) {
                                $n->where('notes', 'NOT LIKE', '%[DIBATALKAN%')
                                    ->where('notes', 'NOT LIKE', '%BATAL%')
                                    ->where('notes', 'NOT LIKE', '%VOID%');
                            });
                    })
                    ->pluck('reference_number')
                    ->filter()
                    ->unique();
            }

            $cancelledMovRefs = \Illuminate\Support\Facades\DB::table('stock_movements')
                ->where(function ($q) {
                    $q->whereIn('status', ['cancelled', 'canceled', 'batal', 'void'])
                        ->orWhere('notes', 'LIKE', '%[DIBATALKAN%');
                })
                ->pluck('reference_number')->filter()->unique();

            if ($cancelledMovRefs->isNotEmpty()) {
                \App\Models\Sale::withoutGlobalScopes()
                    ->whereIn('reference_number', $cancelledMovRefs)
                    ->where('status', '!=', 'cancelled')
                    ->update(['status' => 'cancelled']);
            }

            $cancelledSaleRefs = \App\Models\Sale::withoutGlobalScopes()
                ->where(function ($q) {
                    $q->whereIn('status', ['cancelled', 'canceled', 'batal', 'void'])
                        ->orWhereNotNull('cancelled_at')
                        ->orWhereNotNull('cancellation_reason')
                        ->orWhere('notes', 'LIKE', '%[DIBATALKAN%');
                })
                ->pluck('reference_number')->filter()->unique();

            if ($cancelledSaleRefs->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('stock_movements')
                    ->whereIn('reference_number', $cancelledSaleRefs)
                    ->where(function ($q) {
                        $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
                    })
                    ->update(['status' => 'cancelled']);
            }

            $allCancelledIds = \App\Models\Sale::withoutGlobalScopes()
                ->whereIn('status', ['cancelled', 'canceled', 'batal', 'void'])
                ->orWhereNotNull('cancelled_at')
                ->orWhereNotNull('cancellation_reason')
                ->orWhere('notes', 'LIKE', '%[DIBATALKAN%')
                ->pluck('id');

            foreach ($allCancelledIds as $sId) {
                $accountingService->purgeJournal('sale', $sId);
                $accountingService->purgeJournal('sale_cancellation', $sId);
                $purgedCount++;
            }

            foreach ($cancelledMovRefs->merge($cancelledSaleRefs)->unique() as $cRef) {
                $accountingService->purgeJournalByReference($cRef);
            }

            if ($purgedCount > 0) {
                $results[] = "Cleaned and purged {$purgedCount} cancelled/orphaned sale accounting journals";
            }
        } catch (\Throwable $t) {
            $results[] = 'Sync cancelled sale journals note: ' . $t->getMessage();
        }

        // 3. Ensure incomes table exists
        if (!\Illuminate\Support\Facades\Schema::hasTable('incomes')) {
            \Illuminate\Support\Facades\Schema::create('incomes', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->date('income_date');
                $table->string('category')->default('Lain-lain');
                $table->string('title');
                $table->decimal('amount', 15, 2);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['store_id', 'income_date']);
            });
            $results[] = 'Created table incomes';
        }

        // 3b. Ensure stock_adjustments table exists
        if (!\Illuminate\Support\Facades\Schema::hasTable('stock_adjustments')) {
            \Illuminate\Support\Facades\Schema::create('stock_adjustments', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('quantity_before');
                $table->integer('quantity_change');
                $table->integer('quantity_after');
                $table->string('reason', 50);
                $table->text('notes')->nullable();
                $table->timestamp('reversed_at')->nullable();
                $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('stock_adjustments')->nullOnDelete();
                $table->timestamps();

                $table->index(['store_id', 'product_id', 'created_at']);
                $table->index(['store_id', 'user_id', 'created_at']);
                $table->index(['store_id', 'reason', 'created_at']);
            });
            $results[] = 'Created table stock_adjustments';
        }

        // 4. Ensure Income permissions exist and are attached to Super Admin & Manager
        if (\Illuminate\Support\Facades\Schema::hasTable('permissions') && \Illuminate\Support\Facades\Schema::hasTable('roles')) {
            $newPermissions = [
                [
                    'name' => 'Income List',
                    'category' => 'Pemasukan Toko',
                    'group_name' => 'PEMASUKAN TOKO',
                    'description' => 'Ability to view store incomes list',
                ],
                [
                    'name' => 'Income Create',
                    'category' => 'Pemasukan Toko',
                    'group_name' => 'PEMASUKAN TOKO',
                    'description' => 'Ability to add store income',
                ],
                [
                    'name' => 'Income Edit',
                    'category' => 'Pemasukan Toko',
                    'group_name' => 'PEMASUKAN TOKO',
                    'description' => 'Ability to edit store income',
                ],
                [
                    'name' => 'Income Destroy',
                    'category' => 'Pemasukan Toko',
                    'group_name' => 'PEMASUKAN TOKO',
                    'description' => 'Ability to delete store income',
                ],
            ];

            foreach ($newPermissions as $pData) {
                $permission = \App\Models\Permission::firstOrCreate(
                    ['name' => $pData['name']],
                    $pData
                );

                $roles = \App\Models\Role::whereIn('name', ['Super Admin', 'Manager'])->get();
                foreach ($roles as $role) {
                    if (!$role->permissions->contains('id', $permission->id)) {
                        $role->permissions()->attach($permission->id);
                        $results[] = "Assigned {$pData['name']} to {$role->name}";
                    }
                }
            }
        }

        // 5. Record migrations in migrations table
        if (\Illuminate\Support\Facades\Schema::hasTable('migrations')) {
            $migrationsToRecord = [
                '2026_10_01_113000_add_bank_and_initial_balance_to_stores_table',
                '2026_10_04_120000_create_incomes_table',
                '2026_10_04_120001_add_income_permissions',
                '2026_10_05_120000_add_payment_method_to_stock_movements_table',
                '2026_10_06_110005_add_status_to_stock_movements_table',
                '2026_10_08_120000_add_payment_method_to_sales_table',
                '2026_10_08_120000_create_stock_adjustments_table',
                '2026_10_08_120001_add_reversal_records_to_stock_adjustments',
                '2026_10_09_000000_scope_product_sku_to_store.php',
                '2026_10_09_100000_add_status_and_cancellation_to_sales_table',
                '2026_10_12_220000_add_payment_splits_to_sales_table',
                '2026_10_20_163000_create_accounting_tables',
                '2026_10_20_163100_add_accounting_permissions',
                '2026_10_20_170000_create_phase3_and_4_accounting_tables',
                '2026_10_20_170100_add_phase3_and_4_permissions',
            ];

            foreach ($migrationsToRecord as $migrationName) {
                $cleanName = str_replace('.php', '', $migrationName);
                $exists = \Illuminate\Support\Facades\DB::table('migrations')->where('migration', $cleanName)->exists();
                if (!$exists) {
                    $batch = (\Illuminate\Support\Facades\DB::table('migrations')->max('batch') ?? 0) + 1;
                    \Illuminate\Support\Facades\DB::table('migrations')->insert([
                        'migration' => $cleanName,
                        'batch' => $batch,
                    ]);
                    $results[] = "Recorded {$cleanName} in migrations table";
                }
            }
        }

        // 5b. Backfill historical accounting journals (incremental by default, --refresh with ?sync_accounting=1)
        try {
            $isRefresh = request()->boolean('sync_accounting') || request()->boolean('refresh_accounting');
            \Illuminate\Support\Facades\Artisan::call('accounting:sync-history', $isRefresh ? ['--refresh' => true] : []);
            $accountingSyncOutput = trim(\Illuminate\Support\Facades\Artisan::output());
            if ($accountingSyncOutput) {
                $results[] = 'Sync Accounting Journals: ' . $accountingSyncOutput;
            }
        } catch (\Throwable $t) {
            $results[] = 'Sync Accounting Journals note: ' . $t->getMessage();
        }

        // 5c. Normalize cash/transfer stock in movements (set payment_status = 'paid' and paid_amount)
        try {
            $updatedCashMovements = \Illuminate\Support\Facades\DB::table('stock_movements')
                ->where('type', 'in')
                ->where(function ($q) {
                    $q->whereNull('payment_method')
                        ->orWhereNotIn(\Illuminate\Support\Facades\DB::raw('LOWER(payment_method)'), ['tempo', 'hutang', 'credit']);
                })
                ->where(function ($q) {
                    $q->whereNull('payment_status')
                        ->orWhere('payment_status', '!=', 'partial');
                })
                ->update([
                    'payment_status' => 'paid',
                    'paid_amount' => \Illuminate\Support\Facades\DB::raw('COALESCE(total_price, quantity * COALESCE(purchase_price, 0), 0)'),
                ]);

            if ($updatedCashMovements > 0) {
                $results[] = "Normalized {$updatedCashMovements} cash/transfer stock in movements to paid status";
            }
        } catch (\Throwable $t) {
            $results[] = 'Normalize cash movements note: ' . $t->getMessage();
        }

        // 5d. Backfill historical suppliers into Master Data: Pengaturan Sistem
        try {
            $existingMovementSuppliers = \Illuminate\Support\Facades\DB::table('stock_movements')
                ->whereNotNull('supplier_name')
                ->where('supplier_name', '!=', '')
                ->select('store_id', 'supplier_name')
                ->distinct()
                ->get();
            $existingDateSuppliers = \Illuminate\Support\Facades\DB::table('movement_dates')
                ->whereNotNull('supplier_name')
                ->where('supplier_name', '!=', '')
                ->select('store_id', 'supplier_name')
                ->distinct()
                ->get();

            $seededSupplierCount = 0;
            $allSuppliers = $existingMovementSuppliers->concat($existingDateSuppliers);
            foreach ($allSuppliers as $sup) {
                $name = trim($sup->supplier_name);
                if ($name === '')
                    continue;
                $exists = \Illuminate\Support\Facades\DB::table('settings')
                    ->where('type', 'supplier')
                    ->where('value', $name)
                    ->when($sup->store_id, fn($q) => $q->where('store_id', $sup->store_id))
                    ->exists();
                if (!$exists) {
                    \Illuminate\Support\Facades\DB::table('settings')->insert([
                        'type' => 'supplier',
                        'value' => $name,
                        'store_id' => $sup->store_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $seededSupplierCount++;
                }
            }
            if ($seededSupplierCount > 0) {
                $results[] = "Seeded {$seededSupplierCount} historical suppliers to Master Data settings";
            }
        } catch (\Throwable $t) {
            $results[] = 'Seed suppliers note: ' . $t->getMessage();
        }

        // 5e. Update Vape Break legacy debt initial_amount to 133595000 if not yet paid
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('legacy_debts')) {
                $vbDebt = \Illuminate\Support\Facades\DB::table('legacy_debts')->where('code', 'LEG-VB')->first();
                if ($vbDebt && (float) $vbDebt->paid_amount <= 0 && (float) $vbDebt->initial_amount != 133595000) {
                    \Illuminate\Support\Facades\DB::table('legacy_debts')->where('code', 'LEG-VB')->update([
                        'initial_amount' => 133595000,
                        'remaining_amount' => 133595000,
                        'updated_at' => now(),
                    ]);
                    $results[] = 'Updated Vape Break (VB) legacy debt to Rp 133.595.000';
                }
            }
        } catch (\Throwable $t) {
            $results[] = 'Update Vape Break note: ' . $t->getMessage();
        }

        // 5f. Ensure auto_cash_to_bank is true for all stores
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('stores') && \Illuminate\Support\Facades\Schema::hasColumn('stores', 'auto_cash_to_bank')) {
                \Illuminate\Support\Facades\DB::table('stores')->update(['auto_cash_to_bank' => true]);
                $results[] = 'Ensured auto_cash_to_bank is enabled for all stores';
            }
        } catch (\Throwable $t) {
        }

        // 5g. Ensure default Shift and Attendance Statuses are seeded
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('attendance_statuses')) {
                \Illuminate\Support\Facades\Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\ShiftAndAttendanceStatusSeeder',
                    '--force' => true,
                ]);
                $results[] = 'Seeded default Shift and Attendance Statuses (Total: ' . \App\Models\AttendanceStatus::count() . ')';
            }
        } catch (\Throwable $t) {
            $results[] = 'Shift & Attendance Status seed note: ' . $t->getMessage();
        }

        // 5h. Repair and normalize Inter-Branch Debts
        try {
            \Illuminate\Support\Facades\Artisan::call('accounting:repair-interbranch');
            $repairOutput = trim(\Illuminate\Support\Facades\Artisan::output());
            if ($repairOutput) {
                $results[] = 'Repair Inter-Branch: ' . $repairOutput;
            }
        } catch (\Throwable $t) {
            $results[] = 'Repair Inter-Branch note: ' . $t->getMessage();
        }

        // 6. Clear application caches & reset opcache
        try {
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            $results[] = 'Cleared application caches and reset opcache';
        } catch (\Throwable $t) {
            $results[] = 'Cache clear note: ' . $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine();
        }

        $store3Acc1103 = \App\Models\Account::withoutGlobalScopes()->where('store_id', 3)->where('code', '1103')->first();
        $store3Acc1102 = \App\Models\Account::withoutGlobalScopes()->where('store_id', 3)->where('code', '1102')->first();
        $store3 = \App\Models\Store::find(3);

        $acc1103Balance = $store3Acc1103 ? $store3Acc1103->calculateBalance($store3?->initial_balance_date ? \Carbon\Carbon::parse($store3->initial_balance_date)->toDateString() : null) : 0;

        return response()->json([
            'status' => 'success',
            'php_version' => $phpVersion,
            'changes' => $results,
            'store3_ending_balance' => (float) ($store3?->ending_balance ?? 0),
            'store3_acc1103_balance' => (float) $acc1103Balance,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});




