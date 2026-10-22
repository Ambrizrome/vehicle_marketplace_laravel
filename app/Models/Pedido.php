<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $fillable = ['modelo_id', 'sucursal_id', 'cliente_nombre', 'cliente_email', 'total', 'estado'];

    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }
}
