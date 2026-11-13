<?php

namespace Database\Factories;

use App\Models\Marca;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModeloFactory extends Factory
{
    public function definition()
    {
        return [
            'marca_id' => Marca::factory(),
            'nombre' => $this->faker->word(),
            'anio' => 2023,
            'tipo' => 'Sedán',
            'precio' => 250000.00,
        ];
    }
}