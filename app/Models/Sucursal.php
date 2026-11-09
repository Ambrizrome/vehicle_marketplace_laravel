<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    // Laravel pluralizaría "sucursals", así que indicamos la tabla
    protected $table = 'sucursales';

    protected $fillable = ['nombre', 'ciudad', 'direccion', 'telefono'];

    // Campos internos que no se muestran en el JSON
    protected $hidden = ['created_at', 'updated_at'];

    // Una sucursal ofrece varios modelos (N a N) con su stock
    public function modelos()
    {
        return $this->belongsToMany(Modelo::class, 'modelo_sucursal')->withPivot('stock');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
}
