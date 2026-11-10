<?php

namespace App\Services;

use App\Models\Pedido;
use Illuminate\Pagination\LengthAwarePaginator;

// Una venta es un pedido confirmado (no hay tabla aparte)
class VentaService
{
    // Lista las ventas con modelo, marca y sucursal, de la más reciente a la más antigua (15 por página, ?page=2)
    public function obtenerVentas(): LengthAwarePaginator
    {
        return Pedido::with(['modelo.marca', 'sucursal'])
            ->where('estado', 'confirmado')
            ->latest('id')
            ->paginate(15);
    }
}
