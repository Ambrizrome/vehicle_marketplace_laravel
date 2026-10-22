<?php

use App\Http\Controllers\MarcaController;
use App\Http\Controllers\ModeloController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\SucursalController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Todas las rutas de este archivo llevan el prefijo /api

// Marcas
Route::get('/marcas', [MarcaController::class, 'index']);
Route::get('/marcas/{marca}', [MarcaController::class, 'show']);

// Modelos (productos)
Route::get('/modelos', [ModeloController::class, 'index']);
Route::get('/modelos/{modelo}', [ModeloController::class, 'show']);

// Sucursales (vendedores)
Route::get('/sucursales', [SucursalController::class, 'index']);
Route::get('/sucursales/{sucursal}', [SucursalController::class, 'show']);

// Pedidos
Route::get('/pedidos', [PedidoController::class, 'index']);
Route::post('/pedidos', [PedidoController::class, 'store']);