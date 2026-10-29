<?php

use App\Modules\Core\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// Ứng dụng khách hàng mở ở Phase 3; tới lúc đó đưa người dùng về website.
Route::get('/', fn () => redirect()->route('home'))->name('app.home');

Route::get('/robots.txt', [SeoController::class, 'appRobots'])->name('app.robots');
