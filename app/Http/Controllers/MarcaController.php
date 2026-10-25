<?php

namespace App\Http\Controllers;

use App\Services\MarcaService;

class MarcaController extends Controller
{
    protected $marcaService;

    public function __construct(MarcaService $marcaService)
    {
        $this->marcaService = $marcaService;
    }

    // GET /api/marcas -> lista de marcas con la cantidad de modelos
    public function index()
    {
        return response()->json($this->marcaService->obtenerMarcas());
    }

    // GET /api/marcas/{marca} -> una marca con sus modelos
    public function show($marca)
    {
        return response()->json($this->marcaService->obtenerPorId($marca));
    }
}
