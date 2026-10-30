<?php

use App\Modules\Personal\Http\Controllers\Auth\LoginController;
use App\Modules\Personal\Http\Controllers\Auth\PasswordResetController;
use App\Modules\Personal\Http\Controllers\Auth\RegisterController;
use App\Modules\Personal\Http\Controllers\CategoryController;
use App\Modules\Personal\Http\Controllers\DashboardController;
use App\Modules\Personal\Http\Controllers\TransactionController;
use App\Modules\Personal\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [LoginController::class, 'create'])->name('personal.login');
    Route::post('/dang-nhap', [LoginController::class, 'store'])->middleware('throttle:personal-auth');
    Route::get('/dang-ky', [RegisterController::class, 'create'])->name('personal.register');
    Route::post('/dang-ky', [RegisterController::class, 'store'])->middleware('throttle:personal-auth');
    Route::get('/quen-mat-khau', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/quen-mat-khau', [PasswordResetController::class, 'email'])->middleware('throttle:personal-auth')->name('password.email');
    // Tên route password.reset do thông báo đặt lại mật khẩu của Laravel dùng.
    Route::get('/dat-lai-mat-khau/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [PasswordResetController::class, 'update'])->middleware('throttle:personal-auth')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/dang-xuat', [LoginController::class, 'destroy'])->name('personal.logout');
    Route::get('/tong-quan', DashboardController::class)->name('personal.dashboard');

    Route::resource('vi', WalletController::class)->except('show')
        ->parameters(['vi' => 'wallet'])->names('personal.wallets');

    Route::resource('giao-dich', TransactionController::class)->except('show')
        ->parameters(['giao-dich' => 'transaction'])->names('personal.transactions');

    Route::resource('danh-muc', CategoryController::class)->only(['index', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['danh-muc' => 'category'])->names('personal.categories');
});
