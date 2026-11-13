<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modelo extends Model
{
    use HasFactory;

    protected $fillable = ['marca_id', 'nombre', 'anio', 'tipo', 'precio'];

    // Campos internos que no se muestran en el JSON
    protected $hidden = ['created_at', 'updated_at'];

    // MySQL devuelve los decimal como texto; así salen como número en el JSON
    protected $casts = ['precio' => 'float'];

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