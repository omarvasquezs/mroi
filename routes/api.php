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
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\IntervencionController;
use App\Http\Controllers\TipoIntervencionController;
use App\Http\Controllers\LocalController;

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
Route::get('/medicos-detail/{id}', [MedicoController::class, 'getMedicoDetail']);
Route::get('/pacientes-list', [PacienteController::class, 'getAllPacientes']);
Route::get('/pacientes-with-citas', [PacienteController::class, 'getPacientesWithCitas']); // New route for patients with appointments

// Routes for appointment management
Route::get('citas/check', [CitaController::class, 'checkCita']);
Route::get('citas/availability', [CitaController::class, 'checkAvailability']);
Route::post('citas/validate-conflict', [CitaController::class, 'validateConflict']);
Route::get('citas', [CitaController::class, 'getCitasByMedicoAndFecha']);
Route::post('citas', [CitaController::class, 'store']);
Route::get('citas/{id}', [CitaController::class, 'show']);
Route::put('citas/{id}', [CitaController::class, 'update']);
Route::delete('citas/{id}', [CitaController::class, 'destroy']);

// Routes for intervention management
Route::get('intervenciones/check', [IntervencionController::class, 'checkIntervencion']);
Route::get('intervenciones/availability', [IntervencionController::class, 'checkAvailability']);
Route::post('intervenciones/validate-conflict', [IntervencionController::class, 'validateConflict']);
Route::get('intervenciones', [IntervencionController::class, 'getIntervencionesByMedicoAndFecha']);
Route::post('intervenciones', [IntervencionController::class, 'store']);
Route::get('intervenciones/{id}', [IntervencionController::class, 'show']);
Route::put('intervenciones/{id}', [IntervencionController::class, 'update']);
Route::delete('intervenciones/{id}', [IntervencionController::class, 'destroy']);

Route::get('/tipos-citas-list', [TipoCitaController::class, 'getAllTipoCitas']);
Route::get('/tipos-intervenciones-list', [TipoIntervencionController::class, 'getAllTiposIntervenciones']);

Route::get('/pacientes/search/{term}', [PacienteController::class, 'search']);
Route::post('/comprobantes', [ComprobanteController::class, 'store']);
Route::post('/productos-comprobante', [ProductoComprobanteController::class, 'store']);

Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('pacientes', PacienteController::class);
Route::apiResource('medicos', MedicoController::class);
Route::apiResource('stock', StockController::class);
Route::apiResource('tipos-citas', TipoCitaController::class);
Route::apiResource('tipos-intervenciones', TipoIntervencionController::class);
Route::apiResource('metodos-pago', MetodoPagoController::class);
Route::apiResource('productos-comprobante', ProductoComprobanteController::class);
Route::apiResource('proveedores', ProveedorController::class);
Route::apiResource('materiales', MaterialController::class);

// Routes for Marcas
Route::apiResource('marcas', 'App\Http\Controllers\MarcaController');

Route::get('/productos-comprobante', [ProductoComprobanteController::class, 'index']);
Route::get('/productos-comprobante/{id}', [ProductoComprobanteController::class, 'show']);

Route::get('/pacientes', [PacienteController::class, 'index']);
Route::get('/pacientes/{id}/appointments', [PacienteController::class, 'show']);

Route::get('/comprobantes', [ComprobanteController::class, 'index']);

Route::get('/active-metodos-pago', [MetodoPagoController::class, 'getActiveMetodosPago']);

Route::post('/comprobantes/{id}/generate', [ComprobanteController::class, 'generate']);
Route::get('/comprobantes/{id}/pdf', [ComprobanteController::class, 'generatePdf']);

Route::post('/update-stock', 'App\Http\Controllers\StockController@updateStock');

// Cross-type cita/intervencion conflict validation
Route::post('/validate-cita-intervencion-conflict', [CitaController::class, 'validateCitaIntervencionConflict']);

Route::apiResource('locales', LocalController::class);
Route::get('/locales-list', [App\Http\Controllers\LocalController::class, 'all']);