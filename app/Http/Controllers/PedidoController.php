<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Sucursal;

class PedidoController extends Controller
{
    // GET /api/pedidos -> lista de pedidos con modelo, marca y sucursal
    public function index()
    {
        return response()->json(Pedido::with(['modelo.marca', 'sucursal'])->latest()->get());
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

        // Buscamos el modelo dentro de esa sucursal para leer el stock de la tabla pivote
        $sucursal = Sucursal::findOrFail($datos['sucursal_id']);
        $modelo = $sucursal->modelos()->where('modelos.id', $datos['modelo_id'])->first();

        if (!$modelo || $modelo->pivot->stock < 1) {
            return response()->json(['mensaje' => 'Modelo sin stock en esta sucursal'], 422);
        }

        // Creamos el pedido con el precio actual del modelo como total
        $pedido = Pedido::create([
            'modelo_id'      => $modelo->id,
            'sucursal_id'    => $sucursal->id,
            'cliente_nombre' => $datos['cliente_nombre'],
            'cliente_email'  => $datos['cliente_email'],
            'total'          => $modelo->precio,
            'estado'         => 'pendiente',
        ]);

        // Descontamos una unidad del stock de esa sucursal
        $sucursal->modelos()->updateExistingPivot($modelo->id, ['stock' => $modelo->pivot->stock - 1]);

        return response()->json($pedido, 201);
    }
}
