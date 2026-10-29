<?php

use App\Modules\Content\Http\Controllers\ArticleController;
use App\Modules\Content\Http\Controllers\AuthorController;
use Illuminate\Support\Facades\Route;

Route::get('/kien-thuc', [ArticleController::class, 'index'])->name('content.articles.index');
Route::get('/kien-thuc/{category}', [ArticleController::class, 'category'])->where('category', '[a-z0-9-]{1,120}')->name('content.categories.show');
Route::get('/kien-thuc/{category}/{slug}', [ArticleController::class, 'show'])
    ->where(['category' => '[a-z0-9-]{1,120}', 'slug' => '[a-z0-9-]{1,150}'])
    ->name('content.articles.show');

Route::get('/tac-gia/{slug}', [AuthorController::class, 'show'])->where('slug', '[a-z0-9-]{1,120}')->name('content.authors.show');
