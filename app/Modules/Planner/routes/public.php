<?php

use App\Modules\Planner\Http\Controllers\ToolController;
use Illuminate\Support\Facades\Route;

Route::get('/cong-cu', [ToolController::class, 'index'])->name('planner.tools.index');
Route::get('/cong-cu/{slug}', [ToolController::class, 'show'])->where('slug', '[a-z0-9-]{1,60}')->name('planner.tools.show');
