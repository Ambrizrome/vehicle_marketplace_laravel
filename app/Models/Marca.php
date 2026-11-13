<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'pais_origen'];

    // Campos internos que no se muestran en el JSON
    protected $hidden = ['created_at', 'updated_at'];

    // Una marca tiene muchos modelos (1 a N)
    public function modelos()
    {
        return $this->hasMany(Modelo::class);
    }
}