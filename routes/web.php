<?php

use App\Http\Controllers\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LocationContextController;
use App\Http\Controllers\LocationDataController;
use App\Http\Controllers\StockTransactionController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\RecipeController;
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
    Route::post('stock', [StockTransactionController::class, 'store'])->name('stock.store');

    Route::get('stock-counts/create', [\App\Http\Controllers\StockCountController::class, 'create'])->name('stock-counts.create');
    Route::post('stock-counts', [\App\Http\Controllers\StockCountController::class, 'store'])->name('stock-counts.store');
    Route::get('stock-counts/{stockCount}/edit', [\App\Http\Controllers\StockCountController::class, 'edit'])->name('stock-counts.edit');
    Route::put('stock-counts/{stockCount}', [\App\Http\Controllers\StockCountController::class, 'update'])->name('stock-counts.update');
    Route::post('stock-counts/{stockCount}/post', [\App\Http\Controllers\StockCountController::class, 'post'])->middleware('can:post-stock-count')->name('stock-counts.post');

    Route::resource('menu-items', MenuItemController::class)->except(['show', 'destroy']);
    Route::get('recipes/{menuItem}', [RecipeController::class, 'create'])->name('recipes.create');
    Route::post('recipes/{menuItem}', [RecipeController::class, 'store'])->name('recipes.store');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
