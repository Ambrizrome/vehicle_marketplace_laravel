<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventarioRequest;
use App\Http\Requests\UpdateStockRequest;
use App\Services\InventarioService;

class InventarioController extends Controller
{
    protected $inventarioService;

    public function __construct(InventarioService $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    // GET /api/sucursales/{id}/inventario
    public function index($id)
    {
        return response()->json(
            $this->inventarioService->obtenerInventario($id)
        );
    }

    // POST /api/sucursales/{id}/inventario
    public function store(StoreInventarioRequest $request, $id)
    {
        $modelo = $this->inventarioService->agregarModelo(
            $id,
            $request->validated()
        );

        return response()->json($modelo, 201);
    }

    // PUT /api/sucursales/{id}/inventario/{modeloId}
    public function update(UpdateStockRequest $request, $id, $modeloId)
    {
        $modelo = $this->inventarioService->actualizarStock(
            $id,
            $modeloId,
            $request->validated()['stock']
        );

        return response()->json($modelo);
    }

    // DELETE /api/sucursales/{id}/inventario/{modeloId}
    public function destroy($id, $modeloId)
    {
        $this->inventarioService->eliminarModelo($id, $modeloId);

        return response()->json([
            'message' => 'Modelo eliminado del inventario'
        ]);
    }
}