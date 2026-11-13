<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioService
{
    // Obtiene el inventario completo de una sucursal
    public function obtenerInventario($sucursalId)
    {
        $sucursal = Sucursal::findOrFail($sucursalId);

        return $sucursal->modelos()
            ->with('marca')
            ->get();
    }

    // Agrega un modelo al inventario de una sucursal
    public function agregarModelo($sucursalId, array $datos)
    {
        return DB::transaction(function () use ($sucursalId, $datos) {
            $sucursal = Sucursal::findOrFail($sucursalId);

            // Evita que el mismo modelo se agregue dos veces a la sucursal
            if ($sucursal->modelos()->where('modelo_id', $datos['modelo_id'])->exists()) {
                throw ValidationException::withMessages([
                    'modelo_id' => 'El modelo ya existe en el inventario de esta sucursal.',
                ]);
            }

            $sucursal->modelos()->attach($datos['modelo_id'], [
                'stock' => $datos['stock'],
            ]);

            return $sucursal->modelos()
                ->with('marca')
                ->findOrFail($datos['modelo_id']);
        });
    }

    // Actualiza el stock de un modelo existente
    public function actualizarStock($sucursalId, $modeloId, int $stock)
    {
        return DB::transaction(function () use ($sucursalId, $modeloId, $stock) {
            $sucursal = Sucursal::findOrFail($sucursalId);

            $inventario = DB::table('modelo_sucursal')
                ->where('sucursal_id', $sucursalId)
                ->where('modelo_id', $modeloId)
                ->lockForUpdate()
                ->first();

            if (!$inventario) {
                throw ValidationException::withMessages([
                    'modelo_id' => 'El modelo no existe en el inventario de esta sucursal.',
                ]);
            }

            $sucursal->modelos()->updateExistingPivot($modeloId, [
                'stock' => $stock,
            ]);

            return $sucursal->modelos()
                ->with('marca')
                ->findOrFail($modeloId);
        });
    }

    // Quita un modelo del inventario de una sucursal
    public function eliminarModelo($sucursalId, $modeloId): void
    {
        DB::transaction(function () use ($sucursalId, $modeloId) {
            $sucursal = Sucursal::findOrFail($sucursalId);

            $inventario = DB::table('modelo_sucursal')
                ->where('sucursal_id', $sucursalId)
                ->where('modelo_id', $modeloId)
                ->lockForUpdate()
                ->first();

            // Verifica que el modelo exista en el inventario
            if (!$inventario) {
                throw ValidationException::withMessages([
                    'modelo_id' => 'El modelo no existe en el inventario de esta sucursal.',
                ]);
            }

            // Verifica si existen pedidos pendientes para este modelo
            // en esta sucursal
            $tienePedidosPendientes = Pedido::where('sucursal_id', $sucursalId)
                ->where('modelo_id', $modeloId)
                ->where('estado', 'pendiente')
                ->exists();

            // No permite eliminarlo si tiene pedidos pendientes
            if ($tienePedidosPendientes) {
                throw ValidationException::withMessages([
                    'modelo_id' => 'No se puede eliminar el modelo porque tiene pedidos pendientes.',
                ]);
            }

            // Si no tiene pedidos pendientes, se elimina del inventario
            $sucursal->modelos()->detach($modeloId);
        });
    }
}