<?php

use App\Modules\Compare\Http\Controllers\CreditCardController;
use Illuminate\Support\Facades\Route;

Route::get('/the-tin-dung', [CreditCardController::class, 'index'])->name('compare.credit-cards.index');
