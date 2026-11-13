<?php

namespace Tests\Feature;

use App\Models\Marca;
use App\Models\Modelo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarcaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function cualquier_usuario_puede_ver_el_listado_de_marcas()
    {
        Marca::factory()->count(3)->create();

        $response = $this->getJson('/api/marcas');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    /** @test */
    public function un_administrador_puede_crear_una_marca()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/marcas', [
            'nombre' => 'Honda',
            'pais_origen' => 'Japón',
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['nombre' => 'Honda']);
        
        $this->assertDatabaseHas('marcas', ['nombre' => 'Honda']);
    }

    /** @test */
    public function no_se_puede_borrar_una_marca_si_tiene_modelos_asociados()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['is_admin' => true]);
        $marca = Marca::factory()->create();
        
        // Creamos un modelo asociado a la marca
        Modelo::factory()->create(['marca_id' => $marca->id]);

        $response = $this->actingAs($admin, 'sanctum')->deleteJson("/api/marcas/{$marca->id}");

        // Debe fallar con un código 422 por la ValidationException del Service
        $response->assertStatus(422);
        $this->assertDatabaseHas('marcas', ['id' => $marca->id]);
    }
}