<?php

use Illuminate\Support\Facades\Route;

Route::get('/demo-app', fn () => 'demo app page')->name('demo.app');
