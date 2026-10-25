<?php

namespace App\Http\Controllers;

use App\Services\ModeloService;

class ModeloController extends Controller
{
    protected $modeloService;

    public function __construct(ModeloService $modeloService)
    {
        $this->modeloService = $modeloService;
    }

    // GET /api/modelos -> todos los modelos con su marca
    public function index()
    {
        return response()->json($this->modeloService->obtenerModelos());
    }

    // GET /api/modelos/{modelo} -> un modelo con su marca y las sucursales donde está (con stock)
    public function show($modelo)
    {
        return response()->json($this->modeloService->obtenerPorId($modelo));
    }
}
