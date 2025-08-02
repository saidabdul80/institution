<?php

use Illuminate\Support\Facades\Route;

Route::prefix('result')->group(function() {
    Route::get('/', 'ResultController@index');
});