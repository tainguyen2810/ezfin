<?php

use App\Modules\Markets\Http\Controllers\MarketController;
use Illuminate\Support\Facades\Route;

Route::get('/ty-gia', [MarketController::class, 'fx'])->name('markets.fx');
Route::get('/gia-vang', [MarketController::class, 'gold'])->name('markets.gold');
