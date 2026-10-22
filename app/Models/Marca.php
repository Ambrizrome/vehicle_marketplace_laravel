<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    protected $fillable = ['nombre', 'pais_origen'];

    // Una marca tiene muchos modelos (1 a N)
    public function modelos()
    {
        return $this->hasMany(Modelo::class);
    }
}