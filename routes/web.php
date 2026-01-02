<?php

use App\Http\Controllers\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\LocationContextController;
use App\Http\Controllers\LocationDataController;
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
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
