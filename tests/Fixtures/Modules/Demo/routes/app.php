<?php

use Illuminate\Support\Facades\Route;

Route::get('/demo', fn () => 'demo app page')->name('demo.app');
