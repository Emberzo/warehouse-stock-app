<?php

use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/stock');

Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
Route::post('/stock/add', [StockController::class, 'add'])->name('stock.add');
Route::post('/stock/subtract', [StockController::class, 'subtract'])->name('stock.subtract');
