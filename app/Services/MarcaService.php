<?php

namespace App\Services;

use App\Models\Marca;
use Illuminate\Database\Eloquent\Collection;

class MarcaService
{
    // Lista todas las marcas con la cantidad de modelos de cada una
    public function obtenerMarcas(): Collection
    {
        return Marca::withCount('modelos')->get();
    }

    // Busca una marca por id y la devuelve con sus modelos (404 si no existe)
    public function obtenerPorId($id): Marca
    {
        return Marca::with('modelos')->findOrFail($id);
    }
}
