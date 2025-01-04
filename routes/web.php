<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('template');
});

Route::get('/pacientes/agregar', function () {
    return view('pacientes');
})->name('pacientes.agregar');

Route::get('/', function () {
    return view('main');
});