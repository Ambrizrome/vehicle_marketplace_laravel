<?php

namespace App\Http\Controllers;

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
        return response()->json($this->sucursalService->obtenerSucursales());
    }

    // GET /api/sucursales/{id} -> sucursal con los modelos que ofrece (y la marca de cada uno)
    public function show($id)
    {
        return response()->json($this->sucursalService->obtenerPorId($id));
    }
}
