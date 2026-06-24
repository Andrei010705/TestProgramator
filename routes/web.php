<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductYoutubeController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('products.index'));

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::post('/products/{product}/search-youtube', ProductYoutubeController::class)
    ->name('products.search-youtube');
