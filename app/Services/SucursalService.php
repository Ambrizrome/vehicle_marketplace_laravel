<?php

namespace App\Services;

use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Collection;

class SucursalService
{
    // Lista todas las sucursales
    public function obtenerSucursales(): Collection
    {
        return Sucursal::all();
    }

    // Busca una sucursal por id con los modelos que ofrece (y la marca de cada uno)
    public function obtenerPorId($id): Sucursal
    {
        return Sucursal::with('modelos.marca')->findOrFail($id);
    }
}
