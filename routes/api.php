<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\MedicoController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [UsuarioController::class, 'login']);
Route::post('/logout', [UsuarioController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/usuarios/check-username', [UsuarioController::class, 'checkUsername']);
Route::get('/pacientes/check-doc-identidad', [PacienteController::class, 'checkDocIdentidad']);
Route::get('/medicos/check-dni', [MedicoController::class, 'checkDni']);
Route::get('/medicos/check-cmp', [MedicoController::class, 'checkCmp']);

Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('pacientes', PacienteController::class);
Route::apiResource('medicos', MedicoController::class);