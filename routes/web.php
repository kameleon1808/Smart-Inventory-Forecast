<?php

use App\Http\Controllers\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LocationContextController;
use App\Http\Controllers\LocationDataController;
use App\Http\Controllers\StockTransactionController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\MenuItemUsageController;
use App\Http\Controllers\ExpectedConsumptionReportController;
use App\Http\Controllers\VarianceReportController;
use App\Http\Controllers\ProcurementSuggestionController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\AnomalyController;
use App\Http\Controllers\AnomalyThresholdController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\PeriodLockController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified', 'org.context'])->group(function (): void {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('locations/manage', [AdminLocationController::class, 'index'])
        ->middleware('can:manage-locations')
        ->name('admin.locations.index');

    Route::post('locations', [AdminLocationController::class, 'store'])
        ->middleware('can:manage-locations')
        ->name('admin.locations.store');

    Route::post('locations/assign', [AdminLocationController::class, 'assign'])
        ->middleware('can:manage-locations')
        ->name('admin.locations.assign');

    Route::get('location-data', [LocationDataController::class, 'show'])
        ->middleware('can:view-location-data')
        ->name('location.data');

    Route::post('locations/activate', [LocationContextController::class, 'store'])
        ->name('locations.activate');

    Route::resource('items', ItemController::class)->except(['show', 'destroy']);

    Route::get('stock/ledger', [StockTransactionController::class, 'ledger'])->name('stock.ledger');
    Route::get('stock/receipt', [StockTransactionController::class, 'createReceipt'])->name('stock.receipt.create');
    Route::get('stock/waste', [StockTransactionController::class, 'createWaste'])->name('stock.waste.create');
    Route::get('stock/internal-use', [StockTransactionController::class, 'createInternalUse'])->name('stock.internal.create');
    Route::post('stock', [StockTransactionController::class, 'store'])->middleware('period.lock')->name('stock.store');

    Route::get('stock-counts/create', [\App\Http\Controllers\StockCountController::class, 'create'])->name('stock-counts.create');
    Route::post('stock-counts', [\App\Http\Controllers\StockCountController::class, 'store'])->middleware('period.lock')->name('stock-counts.store');
    Route::get('stock-counts/{stockCount}/edit', [\App\Http\Controllers\StockCountController::class, 'edit'])->name('stock-counts.edit');
    Route::put('stock-counts/{stockCount}', [\App\Http\Controllers\StockCountController::class, 'update'])->middleware('period.lock')->name('stock-counts.update');
    Route::post('stock-counts/{stockCount}/post', [\App\Http\Controllers\StockCountController::class, 'post'])->middleware(['can:post-stock-count', 'period.lock'])->name('stock-counts.post');

    Route::resource('menu-items', MenuItemController::class)->except(['show', 'destroy']);
    Route::get('recipes/{menuItem}', [RecipeController::class, 'create'])->name('recipes.create');
    Route::post('recipes/{menuItem}', [RecipeController::class, 'store'])->name('recipes.store');

    Route::get('menu-usage', [MenuItemUsageController::class, 'create'])->name('menu-usage.create');
    Route::post('menu-usage', [MenuItemUsageController::class, 'store'])->name('menu-usage.store');

    Route::get('reports/expected-consumption', [ExpectedConsumptionReportController::class, 'index'])->name('reports.expected-consumption');
    Route::get('reports/variance', [VarianceReportController::class, 'index'])->name('reports.variance');
    Route::get('forecasts', [ForecastController::class, 'index'])->middleware('can:view-location-data')->name('forecast.index');
    Route::post('forecasts/run', [ForecastController::class, 'generate'])->middleware('can:view-location-data')->name('forecast.run');
    Route::get('anomalies', [AnomalyController::class, 'index'])->middleware('can:view-location-data')->name('anomalies.index');
    Route::get('anomalies/{anomaly}', [AnomalyController::class, 'show'])->middleware('can:view-location-data')->name('anomalies.show');
    Route::post('anomalies/{anomaly}/comments', [AnomalyController::class, 'addComment'])->name('anomalies.comment');
    Route::post('anomalies/{anomaly}/status', [AnomalyController::class, 'updateStatus'])->middleware('can:resolve-anomalies')->name('anomalies.status');
    Route::get('anomaly-thresholds', [AnomalyThresholdController::class, 'index'])->middleware('can:view-location-data')->name('anomalies.thresholds');
    Route::post('anomaly-thresholds', [AnomalyThresholdController::class, 'store'])->middleware('can:resolve-anomalies')->name('anomalies.thresholds.store');
    Route::get('data/import-export', [ImportExportController::class, 'index'])->middleware('can:view-location-data')->name('import-export.index');
    Route::post('data/import', [ImportExportController::class, 'import'])->middleware('can:view-location-data')->name('import.run');
    Route::post('data/export', [ImportExportController::class, 'export'])->middleware('can:view-location-data')->name('export.run');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('can:view-location-data')->name('audit.index');
    Route::get('period-lock', [PeriodLockController::class, 'edit'])->middleware('can:resolve-anomalies')->name('period-lock.edit');
    Route::post('period-lock', [PeriodLockController::class, 'update'])->middleware('can:resolve-anomalies')->name('period-lock.update');

    Route::get('procurement/suggestions', [ProcurementSuggestionController::class, 'index'])->name('procurement.suggestions');
    Route::post('procurement/suggestions', [ProcurementSuggestionController::class, 'store'])->name('procurement.suggestions.store');
    Route::post('procurement/purchase-orders/{purchaseOrder}/approve', [ProcurementSuggestionController::class, 'approve'])->middleware('can:approve-po')->name('procurement.purchase-orders.approve');
    Route::get('procurement/purchase-orders', [PurchaseOrderController::class, 'index'])->name('procurement.purchase-orders.index');
    Route::get('procurement/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('procurement.purchase-orders.show');
    Route::get('procurement/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveForm'])->middleware('can:approve-po')->name('procurement.purchase-orders.receive-form');
    Route::post('procurement/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->middleware('can:approve-po')->name('procurement.purchase-orders.receive');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
