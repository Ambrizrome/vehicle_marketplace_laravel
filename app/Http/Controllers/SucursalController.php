<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSucursalRequest;
use App\Services\SucursalService;

class SucursalController extends Controller
{
    protected $sucursalService;

    public function __construct(SucursalService $sucursalService)
    {
        $this->sucursalService = $sucursalService;
    }

    // GET /api/sucursales
    public function index()
    {
        return response()->json(
            $this->sucursalService->obtenerSucursales()
        );
    }

    // GET /api/sucursales/{id}
    public function show($id)
    {
        return response()->json(
            $this->sucursalService->obtenerPorId($id)
        );
    }

    // POST /api/sucursales -> crea una sucursal (solo admin)
    public function store(StoreSucursalRequest $request)
    {
        $sucursal = $this->sucursalService->crearSucursal(
            $request->validated()
        );

        return response()->json($sucursal, 201);
    }

    // PUT /api/sucursales/{id} -> actualiza una sucursal (solo admin)
    public function update(StoreSucursalRequest $request, $id)
    {
        $sucursal = $this->sucursalService->actualizarSucursal(
            $id,
            $request->validated()
        );

        return response()->json($sucursal);
    }

    // DELETE /api/sucursales/{id} -> elimina una sucursal sin pedidos
    public function destroy($id)
    {
        $this->sucursalService->eliminarSucursal($id);

        return response()->json([
            'message' => 'Sucursal eliminada',
        ]);
    }
}