<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePedidoRequest;
use App\Services\PedidoService;

class PedidoController extends Controller
{
    protected $pedidoService;

    public function __construct(PedidoService $pedidoService)
    {
        $this->pedidoService = $pedidoService;
    }

    // GET /api/pedidos -> lista paginada de pedidos con modelo, marca y sucursal
    public function index()
    {
        return response()->json($this->pedidoService->obtenerPedidos());
    }

    // POST /api/pedidos -> crea un pedido si la sucursal tiene stock del modelo
    public function store(StorePedidoRequest $request)
    {
        // Si no hay stock, el service lanza el error y Laravel responde 422
        $pedido = $this->pedidoService->crearPedido($request->validated());

        return response()->json($pedido, 201);
    }
}
