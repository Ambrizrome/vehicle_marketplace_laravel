<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\Sucursal;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoService
{
    // Lista los pedidos con modelo, marca y sucursal, del más reciente al más antiguo (15 por página, ?page=2)
    // Se ordena por id porque varios pedidos pueden tener el mismo created_at y las páginas se mezclarían
    public function obtenerPedidos(): LengthAwarePaginator
    {
        return Pedido::with(['modelo.marca', 'sucursal'])->latest('id')->paginate(15);
    }

    // Crea un pedido si la sucursal tiene stock del modelo; si no, lanza un error 422 con el motivo
    public function crearPedido(array $datos): Pedido
    {
        // Transacción: si algo falla, no queda un pedido sin descontar stock
        return DB::transaction(function () use ($datos) {
            // lockForUpdate bloquea la fila del stock hasta terminar, así dos pedidos no toman la misma unidad
            $sucursal = Sucursal::findOrFail($datos['sucursal_id']);
            $modelo = $sucursal->modelos()->where('modelos.id', $datos['modelo_id'])->lockForUpdate()->first();

            // ValidationException: Laravel la convierte en una respuesta 422 con el mensaje
            if (!$modelo) {
                throw ValidationException::withMessages(['modelo_id' => 'Este modelo no se vende en esta sucursal']);
            }

            if ($modelo->pivot->stock < 1) {
                throw ValidationException::withMessages(['modelo_id' => 'Modelo sin stock en esta sucursal']);
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
        });
    }

    // Confirma (venta) o cancela un pedido pendiente; si se cancela, la unidad vuelve al stock
    public function cambiarEstado($id, string $estado): Pedido
    {
        return DB::transaction(function () use ($id, $estado) {
            // lockForUpdate: si llegan dos cambios a la vez, el segundo ya ve el estado nuevo (no devuelve stock dos veces)
            $pedido = Pedido::lockForUpdate()->findOrFail($id);

            if ($pedido->estado !== 'pendiente') {
                throw ValidationException::withMessages(['estado' => 'Solo se puede cambiar un pedido pendiente']);
            }

            if ($estado === 'cancelado') {
                DB::table('modelo_sucursal')
                    ->where('modelo_id', $pedido->modelo_id)
                    ->where('sucursal_id', $pedido->sucursal_id)
                    ->increment('stock');
            }

            $pedido->update(['estado' => $estado]);

            return $pedido;
        });
    }
}
