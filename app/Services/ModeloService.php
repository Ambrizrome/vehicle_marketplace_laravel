<?php

namespace App\Services;

use App\Models\Modelo;
use Illuminate\Database\Eloquent\Collection;

class ModeloService
{
    // Lista todos los modelos con su marca
    public function obtenerModelos(): Collection
    {
        return Modelo::with('marca')->get();
    }

    // Busca un modelo por id con su marca y las sucursales donde está (con stock)
    public function obtenerPorId($id): Modelo
    {
        return Modelo::with('marca', 'sucursales')->findOrFail($id);
    }
}
