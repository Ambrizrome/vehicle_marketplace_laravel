<?php

namespace App\Services;

use App\Models\Modelo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ModeloService
{
    // Listar modelos con todos sus filtros opcionales
    public function obtenerModelos(array $filtros): Collection
    {
        return Modelo::with('marca')
            ->when($filtros['marca_id'] ?? null, fn ($q, $v) => $q->where('marca_id', $v))
            ->when($filtros['tipo'] ?? null, fn ($q, $v) => $q->where('tipo', $v))
            ->when($filtros['anio'] ?? null, fn ($q, $v) => $q->where('anio', $v))
            ->when($filtros['precio_min'] ?? null, fn ($q, $v) => $q->where('precio', '>=', $v))
            ->when($filtros['precio_max'] ?? null, fn ($q, $v) => $q->where('precio', '<=', $v))
            ->when($filtros['q'] ?? null, fn ($q, $v) => $q->where('nombre', 'like', "%{$v}%"))
            ->get();
    }

    // Busca un modelo por id con su marca y las sucursales donde está (con stock)
    public function obtenerModeloPorId($id): Modelo
    {
        return Modelo::with('marca', 'sucursales')->findOrFail($id);
    }

    public function crearModelo(array $datos): Modelo
    {
        return Modelo::create($datos);
    }

    public function actualizarModelo($id, array $datos): Modelo
    {
        $modelo = Modelo::findOrFail($id);
        $modelo->update($datos);
        return $modelo;
    }

    // Regla de negocio: No borrar modelo si tiene pedidos
    public function eliminarModelo($id): void
    {
        $modelo = Modelo::findOrFail($id);

        if ($modelo->pedidos()->exists()) {
            throw ValidationException::withMessages([
                'modelo' => 'No se puede borrar: el modelo está en uno o más pedidos.'
            ]);
        }

        $modelo->delete();
    }
}