<?php

use App\Modules\Newsletter\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Đăng ký nhận email (double opt-in). Trang xác nhận và quản lý là noindex.
Route::controller(SubscriptionController::class)->group(function () {
    Route::post('/nhan-tin', 'store')->middleware('throttle:newsletter-signup')->name('newsletter.store');

    Route::middleware('noindex')->group(function () {
        Route::get('/nhan-tin/xac-nhan/{payload}', 'confirmForm')->name('newsletter.confirm');
        Route::post('/nhan-tin/xac-nhan/{payload}', 'confirm')->middleware('throttle:newsletter-signup');
        Route::get('/nhan-tin/quan-ly/{token}', 'manage')->where('token', '[A-Za-z0-9]{40}')->name('newsletter.manage');
        Route::post('/nhan-tin/quan-ly/{token}', 'update')->where('token', '[A-Za-z0-9]{40}');
        Route::post('/nhan-tin/huy/{token}', 'unsubscribe')->where('token', '[A-Za-z0-9]{40}')->name('newsletter.unsubscribe');
    });
});
