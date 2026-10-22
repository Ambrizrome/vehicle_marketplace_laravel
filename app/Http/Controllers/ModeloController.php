<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Modelo;

class ModeloController extends Controller
{
    // GET /api/modelos -> todos los modelos con su marca
    public function index()
    {
        return response()->json(Modelo::with('marca')->get());
    }

    // GET /api/modelos/{modelo} -> un modelo con su marca y las sucursales donde está (con stock)
    public function show(Modelo $modelo)
    {
        return response()->json($modelo->load('marca', 'sucursales'));
    }
}
