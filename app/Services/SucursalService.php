<?php

namespace App\Services;

use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class SucursalService
{
    // Lista todas las sucursales
    public function obtenerSucursales(): Collection
    {
        return Sucursal::all();
    }

    // Busca una sucursal por id con los modelos que ofrece y sus marcas
    public function obtenerPorId($id): Sucursal
    {
        return Sucursal::with('modelos.marca')->findOrFail($id);
    }

    // Crea una nueva sucursal
    public function crearSucursal(array $datos): Sucursal
    {
        return Sucursal::create($datos);
    }

    // Actualiza una sucursal existente
    public function actualizarSucursal($id, array $datos): Sucursal
    {
        $sucursal = Sucursal::findOrFail($id);

        $sucursal->update($datos);

        return $sucursal;
    }

    // Elimina una sucursal solamente si no tiene pedidos
    public function eliminarSucursal($id): void
    {
        $sucursal = Sucursal::findOrFail($id);

        if ($sucursal->pedidos()->exists()) {
            throw ValidationException::withMessages([
                'sucursal' => 'No se puede borrar: la sucursal tiene pedidos registrados',
            ]);
        }

        $sucursal->delete();
    }
}