<?php

use App\Modules\Partner\Http\Controllers\OutboundController;
use Illuminate\Support\Facades\Route;

Route::get('/di/{code}', OutboundController::class)
    ->where('code', '[A-Za-z0-9]{12}')
    ->middleware('throttle:partner-redirect')
    ->name('partner.go');
