<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sucursal;

class SucursalController extends Controller
{
    // GET /api/sucursales
    public function index()
    {
        return response()->json(Sucursal::all());
    }

    // GET /api/sucursales/{sucursal} -> sucursal con los modelos que ofrece (y la marca de cada uno)
    public function show(Sucursal $sucursal)
    {
        return response()->json($sucursal->load('modelos.marca'));
    }
}
