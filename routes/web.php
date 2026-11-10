<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// El proyecto es solo una API REST (no tiene páginas web); la raíz indica dónde están las rutas
Route::get('/', function () {
    return response()->json(['message' => 'API del catálogo de autos: las rutas están en /api (ver README)']);
});
