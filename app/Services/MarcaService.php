<?php

namespace App\Services;

use App\Models\Marca;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class MarcaService
{
    // Listar marcas con opción de filtro por nombre (q=...)
    public function obtenerMarcas(?string $busqueda = null): Collection
    {
        return Marca::query()
            ->when($busqueda, fn ($query, $texto) => $query->where('nombre', 'like', "%{$texto}%"))
            ->get();
    }

    public function obtenerMarcaPorId($id): Marca
    {
        return Marca::findOrFail($id);
    }

    public function crearMarca(array $datos): Marca
    {
        return Marca::create($datos);
    }

    public function actualizarMarca($id, array $datos): Marca
    {
        $marca = Marca::findOrFail($id);
        $marca->update($datos);
        return $marca;
    }

    // Regla de negocio: No borrar marca si tiene modelos
    public function eliminarMarca($id): void
    {
        $marca = Marca::findOrFail($id);

        if ($marca->modelos()->exists()) {
            throw ValidationException::withMessages([
                'marca' => 'No se puede borrar: la marca tiene modelos asociados.'
            ]);
        }

        $marca->delete();
    }
}