<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MarcaFactory extends Factory
{
    public function definition()
    {
        return [
            'nombre' => $this->faker->unique()->company(),
            'pais_origen' => $this->faker->country(),
        ];
    }
}