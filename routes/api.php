<?php

use App\Http\Controllers\Api\ItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'org.context'])->group(function (): void {
    Route::apiResource('items', ItemController::class)
        ->only(['index', 'show', 'store', 'update'])
        ->names('api.items');
});

Route::middleware(['web', 'auth'])->get('/user', function (Request $request) {
    return $request->user();
});
