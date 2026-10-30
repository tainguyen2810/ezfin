<?php

use App\Modules\Core\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;

// Trang đầu của app: vào ứng dụng quản lý tiền khi đang bật, nếu không thì về website.
Route::get('/', fn () => Feature::active('personal') && Route::has('personal.dashboard')
    ? redirect()->route('personal.dashboard')
    : redirect()->route('home'))->name('app.home');

Route::get('/robots.txt', [SeoController::class, 'appRobots'])->name('app.robots');
