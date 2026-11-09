<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $fillable = ['modelo_id', 'sucursal_id', 'cliente_nombre', 'cliente_email', 'total', 'estado'];

    // No se muestran en el JSON: el email es dato personal; created_at se deja como fecha del pedido
    protected $hidden = ['cliente_email', 'updated_at'];

    // MySQL devuelve los decimal como texto; así sale como número en el JSON
    protected $casts = ['total' => 'float'];

    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }
}
