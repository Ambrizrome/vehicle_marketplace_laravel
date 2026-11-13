<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuscarModelosRequest;
use App\Http\Requests\StoreModeloRequest;
use App\Services\ModeloService;

class ModeloController extends Controller
{
    protected ModeloService $modeloService;

    public function __construct(ModeloService $modeloService)
    {
        $this->modeloService = $modeloService;
    }

    public function index(BuscarModelosRequest $request)
    {
        $modelos = $this->modeloService->obtenerModelos($request->validated());
        return response()->json($modelos);
    }

    public function show($id)
    {
        return response()->json($this->modeloService->obtenerModeloPorId($id));
    }

    public function store(StoreModeloRequest $request)
    {
        $modelo = $this->modeloService->crearModelo($request->validated());
        return response()->json($modelo, 201);
    }

    public function update(StoreModeloRequest $request, $id)
    {
        $modelo = $this->modeloService->actualizarModelo($id, $request->validated());
        return response()->json($modelo, 200);
    }

    public function destroy($id)
    {
        $this->modeloService->eliminarModelo($id);
        return response()->json(['message' => 'Modelo eliminado'], 200);
    }
}