<?php

use App\Modules\Personal\Http\Controllers\AccountController;
use App\Modules\Personal\Http\Controllers\Auth\LoginController;
use App\Modules\Personal\Http\Controllers\Auth\PasswordResetController;
use App\Modules\Personal\Http\Controllers\Auth\RegisterController;
use App\Modules\Personal\Http\Controllers\BudgetController;
use App\Modules\Personal\Http\Controllers\CategoryController;
use App\Modules\Personal\Http\Controllers\DashboardController;
use App\Modules\Personal\Http\Controllers\GoalController;
use App\Modules\Personal\Http\Controllers\NetWorthController;
use App\Modules\Personal\Http\Controllers\RecurringTransactionController;
use App\Modules\Personal\Http\Controllers\ReportController;
use App\Modules\Personal\Http\Controllers\StatementImportController;
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

    // Nhập sao kê: đặt trước resource giao dịch.
    Route::get('/giao-dich/nhap-sao-ke', [StatementImportController::class, 'create'])->name('personal.imports.create');
    Route::post('/giao-dich/nhap-sao-ke', [StatementImportController::class, 'store'])->middleware('throttle:20,1')->name('personal.imports.store');
    Route::get('/giao-dich/nhap-sao-ke/{token}', [StatementImportController::class, 'show'])->name('personal.imports.show');
    Route::post('/giao-dich/nhap-sao-ke/{token}', [StatementImportController::class, 'import'])->name('personal.imports.import');

    Route::resource('giao-dich', TransactionController::class)->except('show')
        ->parameters(['giao-dich' => 'transaction'])->names('personal.transactions');

    Route::resource('dinh-ky', RecurringTransactionController::class)->except('show')
        ->parameters(['dinh-ky' => 'recurring'])->names('personal.recurring');
    Route::post('/dinh-ky/{recurring}/ghi-nhan', [RecurringTransactionController::class, 'record'])->name('personal.recurring.record');
    Route::post('/dinh-ky/{recurring}/bo-qua', [RecurringTransactionController::class, 'skip'])->name('personal.recurring.skip');

    Route::resource('danh-muc', CategoryController::class)->only(['index', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['danh-muc' => 'category'])->names('personal.categories');

    Route::get('/ngan-sach', [BudgetController::class, 'index'])->name('personal.budgets.index');
    Route::get('/ngan-sach/thiet-lap', [BudgetController::class, 'edit'])->name('personal.budgets.edit');
    Route::put('/ngan-sach', [BudgetController::class, 'update'])->name('personal.budgets.update');
    Route::post('/ngan-sach/sao-chep', [BudgetController::class, 'copy'])->name('personal.budgets.copy');

    Route::resource('muc-tieu', GoalController::class)
        ->parameters(['muc-tieu' => 'goal'])->names('personal.goals');
    Route::post('/muc-tieu/{goal}/gop', [GoalController::class, 'contribute'])->name('personal.goals.contribute');
    Route::delete('/muc-tieu/{goal}/gop/{contribution}', [GoalController::class, 'destroyContribution'])->name('personal.goals.contributions.destroy');

    Route::resource('tai-san-no', NetWorthController::class)->except('show')
        ->parameters(['tai-san-no' => 'item'])->names('personal.net-worth');

    Route::get('/bao-cao', [ReportController::class, 'index'])->name('personal.reports.index');

    // /tai-khoan (không dùng /cai-dat: đường dẫn đó là trang cài đặt hệ thống).
    Route::get('/tai-khoan', [AccountController::class, 'edit'])->name('personal.settings.edit');
    Route::put('/tai-khoan', [AccountController::class, 'update'])->name('personal.settings.update');
    Route::put('/tai-khoan/mat-khau', [AccountController::class, 'password'])->middleware('throttle:personal-auth')->name('personal.settings.password');
    Route::get('/tai-khoan/xuat-giao-dich.csv', [AccountController::class, 'export'])->name('personal.settings.export');
    Route::delete('/tai-khoan', [AccountController::class, 'destroy'])->middleware('throttle:personal-auth')->name('personal.settings.destroy');
});
