<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\ModeloController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\SucursalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Todas las rutas de este archivo llevan el prefijo /api
// whereNumber('id'): si el id no es número, responde 404 sin consultar la BD

// Autenticación: register y login devuelven un token para usar como "Authorization: Bearer <token>"
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Marcas
Route::get('/marcas', [MarcaController::class, 'index']);
Route::get('/marcas/{id}', [MarcaController::class, 'show'])->whereNumber('id');

// Modelos (productos)
Route::get('/modelos', [ModeloController::class, 'index']);
Route::get('/modelos/{id}', [ModeloController::class, 'show'])->whereNumber('id');

// Sucursales (vendedores)
Route::get('/sucursales', [SucursalController::class, 'index']);
Route::get('/sucursales/{id}', [SucursalController::class, 'show'])->whereNumber('id');

// Rutas que requieren token (sin token o con uno inválido responden 401)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Pedidos (llevan datos de clientes, por eso no son públicos)
    Route::get('/pedidos', [PedidoController::class, 'index']);
    Route::post('/pedidos', [PedidoController::class, 'store']);
});