<?php

use App\Modules\Compare\Http\Controllers\CreditCardController;
use App\Modules\Compare\Http\Controllers\SavingsController;
use Illuminate\Support\Facades\Route;

Route::get('/the-tin-dung', [CreditCardController::class, 'index'])->name('compare.credit-cards.index');
// Đặt trước route chi tiết để "so-sanh" không bị hiểu là slug thẻ.
Route::get('/the-tin-dung/so-sanh', [CreditCardController::class, 'compare'])->name('compare.credit-cards.compare');
Route::get('/the-tin-dung/{slug}', [CreditCardController::class, 'show'])->where('slug', '[a-z0-9-]{1,150}')->name('compare.credit-cards.show');

Route::get('/lai-suat-tiet-kiem', [SavingsController::class, 'index'])->name('compare.savings.index');
