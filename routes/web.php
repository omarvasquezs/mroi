<?php

use Illuminate\Support\Facades\Route;

// Ensure there are no conflicting routes here

Route::get('/{any}', function () {
    return view('welcome');

})->where('any', '.*');