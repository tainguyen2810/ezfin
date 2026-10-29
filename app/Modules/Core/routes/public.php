<?php

use App\Modules\Core\Http\Controllers\SeoController;
use App\Modules\Core\Http\Controllers\StyleGuideController;
use Illuminate\Support\Facades\Route;

// Trang tạm cho tới khi trang chủ thật hoàn thành (Phase 1, task 17).
Route::view('/', 'coming-soon')->name('home');

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

// Trang mẫu giao diện nội bộ: không bao giờ có trên production.
if (! app()->isProduction()) {
    Route::middleware('noindex')->prefix('_ui')->name('style-guide.')->group(function () {
        Route::get('/', [StyleGuideController::class, 'components'])->name('components');
        Route::get('/so-sanh', [StyleGuideController::class, 'compare'])->name('compare');
        Route::get('/ung-dung', [StyleGuideController::class, 'app'])->name('app');
    });
}
