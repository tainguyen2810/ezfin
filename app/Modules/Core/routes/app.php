<?php

use Illuminate\Support\Facades\Route;

// Ứng dụng khách hàng mở ở Phase 3; tới lúc đó đưa người dùng về website.
Route::get('/', fn () => redirect()->route('home'))->name('app.home');
