<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Marca;

class MarcaController extends Controller
{
    // GET /api/marcas -> lista de marcas con la cantidad de modelos
    public function index()
    {
        return response()->json(Marca::withCount('modelos')->get());
    }

    // GET /api/marcas/{marca} -> una marca con sus modelos
    public function show(Marca $marca)
    {
        return response()->json($marca->load('modelos'));
    }
}
