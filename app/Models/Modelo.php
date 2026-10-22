<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modelo extends Model
{
    protected $fillable = ['marca_id', 'nombre', 'anio', 'tipo', 'precio'];

    // Un modelo pertenece a una marca
    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    // Un modelo está disponible en varias sucursales (N a N) con su stock
    public function sucursales()
    {
        return $this->belongsToMany(Sucursal::class, 'modelo_sucursal')->withPivot('stock');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
}