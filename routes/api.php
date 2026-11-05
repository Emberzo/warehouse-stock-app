<?php

use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [StockController::class, 'index'])->name('api.products.index');
Route::post('/stock/add', [StockController::class, 'add'])->name('api.stock.add');
Route::post('/stock/subtract', [StockController::class, 'subtract'])->name('api.stock.subtract');
