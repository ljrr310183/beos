<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('products.index');
    Route::post('/', [ProductController::class, 'store'])->name('products.create');
    Route::get('/{id}', [ProductController::class, 'show'])
        ->whereNumber('id')
        ->name('products.show');
    Route::put('/{id}', [ProductController::class, 'update'])
        ->whereNumber('id')
        ->name('products.update');
    Route::delete('/{id}', [ProductController::class, 'destroy'])
        ->whereNumber('id')
        ->name('products.destroy');
    Route::get('/{id}/prices', [ProductController::class, 'prices'])
        ->whereNumber('id')
        ->name('products.prices');
    Route::post('/{id}/prices', [ProductController::class, 'storePrices'])
        ->whereNumber('id')
        ->name('products.storePrices');
});
