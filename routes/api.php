<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TipoCitaController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\ProductoComprobanteController;
use App\Http\Controllers\ProveedorController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [UsuarioController::class, 'login']);
Route::post('/logout', [UsuarioController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/change-password', [UsuarioController::class, 'changePassword']);

Route::get('/usuarios/check-username', [UsuarioController::class, 'checkUsername']);
Route::get('/pacientes/check-doc-identidad', [PacienteController::class, 'checkDocIdentidad']);
Route::get('/medicos/check-dni', [MedicoController::class, 'checkDni']);
Route::get('/medicos/check-cmp', [MedicoController::class, 'checkCmp']);
Route::get('/proveedores/check-ruc', [ProveedorController::class, 'checkRuc']);
Route::get('/proveedores/check-correo', [ProveedorController::class, 'checkCorreo']);
Route::get('/medicos-list', [MedicoController::class, 'getAllMedicos']);
Route::get('/pacientes-list', [PacienteController::class, 'getAllPacientes']);

Route::post('/citas', [CitaController::class, 'store']);
Route::get('/citas', [CitaController::class, 'getCitasByMedicoAndFecha']);
Route::get('/citas/check', [CitaController::class, 'checkCita']);

Route::get('/tipos-citas-list', [TipoCitaController::class, 'getAllTipoCitas']);

Route::get('/pacientes/search/{term}', [PacienteController::class, 'search']);
Route::post('/comprobantes', [ComprobanteController::class, 'store']);
Route::post('/productos-comprobante', [ProductoComprobanteController::class, 'store']);

Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('pacientes', PacienteController::class);
Route::apiResource('medicos', MedicoController::class);
Route::apiResource('stock', StockController::class);
Route::apiResource('tipos-citas', TipoCitaController::class);
Route::apiResource('metodos-pago', MetodoPagoController::class);
Route::apiResource('productos-comprobante', ProductoComprobanteController::class);
Route::apiResource('proveedores', ProveedorController::class);

Route::get('/productos-comprobante', [ProductoComprobanteController::class, 'index']);
Route::get('/productos-comprobante/{id}', [ProductoComprobanteController::class, 'show']);

Route::get('/pacientes', [PacienteController::class, 'index']);
Route::get('/pacientes/{id}/appointments', [PacienteController::class, 'show']);