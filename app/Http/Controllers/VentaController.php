<?php

namespace App\Http\Controllers;

use App\Services\VentaService;

class VentaController extends Controller
{
    protected $ventaService;

    public function __construct(VentaService $ventaService)
    {
        $this->ventaService = $ventaService;
    }

    // GET /api/ventas -> lista paginada de pedidos confirmados (solo admin)
    public function index()
    {
        return response()->json($this->ventaService->obtenerVentas());
    }
}
