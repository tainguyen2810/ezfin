<?php

use App\Modules\Core\Http\Controllers\HomeController;
use App\Modules\Core\Http\Controllers\SeoController;
use App\Modules\Core\Http\Controllers\StyleGuideController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Trang thông tin và pháp lý.
Route::view('/gioi-thieu', 'core::legal.about')->name('legal.about');
Route::view('/phuong-phap-danh-gia', 'core::legal.methodology')->name('legal.methodology');
Route::view('/cong-bo-lien-ket', 'core::legal.disclosure')->name('legal.disclosure');
Route::view('/dieu-khoan', 'core::legal.terms')->name('legal.terms');
Route::view('/quyen-rieng-tu', 'core::legal.privacy')->name('legal.privacy');
Route::view('/lien-he', 'core::legal.contact')->name('legal.contact');

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
