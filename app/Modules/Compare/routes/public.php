<?php

use App\Modules\Compare\Http\Controllers\CreditCardController;
use Illuminate\Support\Facades\Route;

Route::get('/the-tin-dung', [CreditCardController::class, 'index'])->name('compare.credit-cards.index');
// Đặt trước route chi tiết /the-tin-dung/{slug} (task sau) để không bị trùng.
Route::get('/the-tin-dung/so-sanh', [CreditCardController::class, 'compare'])->name('compare.credit-cards.compare');
