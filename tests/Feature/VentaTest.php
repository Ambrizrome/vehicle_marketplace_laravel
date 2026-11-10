<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use App\Models\User;

// Pruebas de GET /api/ventas (corren en SQLite en memoria, no tocan larosite)
class VentaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogoSeeder::class);
    }

    public function test_admin_ve_solo_los_pedidos_confirmados()
    {
        // Tres pedidos de una combinación con 3 unidades
        $combinacion = DB::table('modelo_sucursal')->where('stock', 3)->first();
        Sanctum::actingAs(User::factory()->create());

        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $ids[] = $this->postJson('/api/pedidos', [
                'modelo_id'      => $combinacion->modelo_id,
                'sucursal_id'    => $combinacion->sucursal_id,
                'cliente_nombre' => 'Juan Pérez',
                'cliente_email'  => 'juan@test.com',
            ])->assertStatus(201)->json('id');
        }

        // Uno confirmado, uno cancelado y uno pendiente: solo el confirmado es venta
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));
        $this->patchJson("/api/pedidos/{$ids[0]}/estado", ['estado' => 'confirmado'])->assertStatus(200);
        $this->patchJson("/api/pedidos/{$ids[1]}/estado", ['estado' => 'cancelado'])->assertStatus(200);

        $this->getJson('/api/ventas')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ids[0])
            ->assertJsonPath('data.0.estado', 'confirmado');
    }

    public function test_usuario_sin_admin_responde_403()
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/ventas')->assertStatus(403);
    }

    public function test_sin_token_responde_401()
    {
        $this->getJson('/api/ventas')->assertStatus(401);
    }
}
