<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PedidoService;

class PedidoController extends Controller
{
    protected $pedidoService;

    public function __construct(PedidoService $pedidoService)
    {
        $this->pedidoService = $pedidoService;
    }

    // GET /api/pedidos -> lista de pedidos con modelo, marca y sucursal
    public function index()
    {
        return response()->json($this->pedidoService->obtenerPedidos());
    }

    // POST /api/pedidos -> crea un pedido si la sucursal tiene stock del modelo
    public function store(Request $request)
    {
        // Validación básica (si falla, Laravel responde 422 con los errores)
        $datos = $request->validate([
            'modelo_id'      => 'required|exists:modelos,id',
            'sucursal_id'    => 'required|exists:sucursales,id',
            'cliente_nombre' => 'required|string|max:100',
            'cliente_email'  => 'required|email',
        ]);

        $pedido = $this->pedidoService->crearPedido($datos);

        if (!$pedido) {
            return response()->json(['mensaje' => 'Modelo sin stock en esta sucursal'], 422);
        }

        return response()->json($pedido, 201);
    }
}
