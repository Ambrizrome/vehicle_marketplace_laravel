<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMarcaRequest;
use App\Services\MarcaService;
use Illuminate\Http\Request;

class MarcaController extends Controller
{
    protected MarcaService $marcaService;

    public function __construct(MarcaService $marcaService)
    {
        $this->marcaService = $marcaService;
    }

    public function index(Request $request)
    {
        $marcas = $this->marcaService->obtenerMarcas($request->query('q'));
        return response()->json($marcas);
    }

    public function show($id)
    {
        return response()->json($this->marcaService->obtenerMarcaPorId($id));
    }

    public function store(StoreMarcaRequest $request)
    {
        $marca = $this->marcaService->crearMarca($request->validated());
        return response()->json($marca, 201);
    }

    public function update(StoreMarcaRequest $request, $id)
    {
        $marca = $this->marcaService->actualizarMarca($id, $request->validated());
        return response()->json($marca, 200);
    }

    public function destroy($id)
    {
        $this->marcaService->eliminarMarca($id);
        return response()->json(['message' => 'Marca eliminada'], 200);
    }
}