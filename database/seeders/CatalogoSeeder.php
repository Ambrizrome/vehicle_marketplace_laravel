<?php

namespace Database\Seeders;

use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class CatalogoSeeder extends Seeder
{
    public function run()
    {
        // Marca => [país, [ [modelo, año, tipo, precio USD], ... ]]
        // Los precios son aproximados, solo para el ejercicio.
        $catalogo = [
            'Ferrari'       => ['Italia',   [['296 GTB', 2023, 'Deportivo', 330000], ['Roma', 2023, 'Coupé', 245000]]],
            'Lamborghini'   => ['Italia',   [['Huracán', 2023, 'Deportivo', 260000], ['Urus', 2023, 'SUV', 230000]]],
            'Porsche'       => ['Alemania', [['911 Carrera', 2023, 'Deportivo', 125000], ['Taycan', 2023, 'Sedán eléctrico', 100000]]],
            'Mercedes-Benz' => ['Alemania', [['AMG GT', 2023, 'Coupé', 150000], ['Clase G', 2023, 'SUV', 180000]]],
            'BMW'           => ['Alemania', [['M4 Competition', 2023, 'Coupé', 85000], ['M5', 2023, 'Sedán', 110000]]],
        ];

        foreach ($catalogo as $nombreMarca => [$pais, $modelos]) {
            $marca = Marca::create(['nombre' => $nombreMarca, 'pais_origen' => $pais]);

            foreach ($modelos as [$nombre, $anio, $tipo, $precio]) {
                // create() desde la relación asigna marca_id automáticamente
                $marca->modelos()->create([
                    'nombre' => $nombre,
                    'anio'   => $anio,
                    'tipo'   => $tipo,
                    'precio' => $precio,
                ]);
            }
        }

        // Cambia las ciudades por las que quieras
        $sucursales = [
            Sucursal::create(['nombre' => 'Luxury Cars Centro',   'ciudad' => 'Ciudad de México', 'direccion' => 'Av. Reforma 100',      'telefono' => '555-0101']),
            Sucursal::create(['nombre' => 'Luxury Cars Norte',    'ciudad' => 'Monterrey',        'direccion' => 'Av. Constitución 200', 'telefono' => '818-0202']),
            Sucursal::create(['nombre' => 'Luxury Cars Pacífico', 'ciudad' => 'Guadalajara',      'direccion' => 'Av. Vallarta 300',     'telefono' => '333-0303']),
        ];

        // Stock determinista (0 a 3) para poder probar el caso "sin stock"
        foreach ($sucursales as $sucursal) {
            foreach (Modelo::all() as $modelo) {
                $sucursal->modelos()->attach($modelo->id, ['stock' => ($modelo->id + $sucursal->id) % 4]);
            }
        }
    }
}
