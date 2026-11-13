<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\ModeloController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\VentaController;
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

// Marcas (públicas para consulta)[cite: 1]
Route::get('/marcas', [MarcaController::class, 'index']);
Route::get('/marcas/{id}', [MarcaController::class, 'show'])
    ->whereNumber('id');

// Modelos (productos) (públicas para consulta)[cite: 1]
Route::get('/modelos', [ModeloController::class, 'index']);
Route::get('/modelos/{id}', [ModeloController::class, 'show'])
    ->whereNumber('id');

// Sucursales (vendedores)
Route::get('/sucursales', [SucursalController::class, 'index']);
Route::get('/sucursales/{id}', [SucursalController::class, 'show'])
    ->whereNumber('id');

// Inventario de una sucursal (consulta pública)
Route::get('/sucursales/{id}/inventario', [InventarioController::class, 'index'])
    ->whereNumber('id');

// Rutas que requieren token (sin token o con uno inválido responden 401)[cite: 1]
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Cualquier usuario con token puede crear un pedido[cite: 1]
    Route::post('/pedidos', [PedidoController::class, 'store']);

    // Solo admin (con token pero sin permiso responde 403)[cite: 1]
    Route::middleware('can:admin')->group(function () {

        // La lista lleva nombres de clientes, por eso solo la ve el admin
        Route::get('/pedidos', [PedidoController::class, 'index']);

        Route::patch('/pedidos/{id}/estado', [PedidoController::class, 'cambiarEstado'])
            ->whereNumber('id');

        // Ventas = pedidos confirmados[cite: 1]
        Route::get('/ventas', [VentaController::class, 'index']);

        // ----------------------------------------------------------------
        // MÓDULO: CRUD de Marcas y Modelos (Crear, Editar, Borrar - Solo Admin)[cite: 1]
        // ----------------------------------------------------------------
        
        // Marcas
        Route::post('/marcas', [MarcaController::class, 'store']);
        Route::put('/marcas/{id}', [MarcaController::class, 'update'])->whereNumber('id');
        Route::delete('/marcas/{id}', [MarcaController::class, 'destroy'])->whereNumber('id');

        // Modelos
        Route::post('/modelos', [ModeloController::class, 'store']);
        Route::put('/modelos/{id}', [ModeloController::class, 'update'])->whereNumber('id');
        Route::delete('/modelos/{id}', [ModeloController::class, 'destroy'])->whereNumber('id');
    });
});

// Sucursales e inventario: crear, editar y borrar (solo admin)
Route::middleware(['auth:sanctum', 'can:admin'])->group(function () {

    // CRUD de sucursales
    Route::post('/sucursales', [SucursalController::class, 'store']);

    Route::put('/sucursales/{id}', [SucursalController::class, 'update'])
        ->whereNumber('id');

    Route::delete('/sucursales/{id}', [SucursalController::class, 'destroy'])
        ->whereNumber('id');

    // Inventario: agregar un modelo a una sucursal
    Route::post('/sucursales/{id}/inventario', [InventarioController::class, 'store'])
        ->whereNumber('id');

    // Inventario: actualizar stock de un modelo
    Route::put('/sucursales/{id}/inventario/{modeloId}', [InventarioController::class, 'update'])
        ->whereNumber('id')
        ->whereNumber('modeloId');

    // Inventario: eliminar un modelo de una sucursal
    Route::delete('/sucursales/{id}/inventario/{modeloId}', [InventarioController::class, 'destroy'])
        ->whereNumber('id')
        ->whereNumber('modeloId');
});