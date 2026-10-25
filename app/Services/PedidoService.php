<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Collection;

class PedidoService
{
    // Lista los pedidos con modelo, marca y sucursal, del más reciente al más antiguo
    public function obtenerPedidos(): Collection
    {
        return Pedido::with(['modelo.marca', 'sucursal'])->latest()->get();
    }

    // Crea un pedido si la sucursal tiene stock del modelo; devuelve null si no hay stock
    public function crearPedido(array $datos): ?Pedido
    {
        // Buscamos el modelo dentro de esa sucursal para leer el stock de la tabla pivote
        $sucursal = Sucursal::findOrFail($datos['sucursal_id']);
        $modelo = $sucursal->modelos()->where('modelos.id', $datos['modelo_id'])->first();

        if (!$modelo || $modelo->pivot->stock < 1) {
            return null;
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

        return $pedido;
    }
}
