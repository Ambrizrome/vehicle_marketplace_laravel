<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use App\Models\User;

// Pruebas de PATCH /api/pedidos/{id}/estado (corren en SQLite en memoria, no tocan larosite)
class PedidoEstadoTest extends TestCase
{
    use RefreshDatabase;

    /** @var \stdClass Fila de modelo_sucursal (id, modelo_id, sucursal_id, stock) */
    private $combinacion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogoSeeder::class);

        // Combinación con 2 unidades: al crear el pedido queda en 1
        $this->combinacion = DB::table('modelo_sucursal')->where('stock', 2)->first();
    }

    // Crea un pedido pendiente por la API (como usuario normal) y devuelve su id
    private function crearPedido(): int
    {
        Sanctum::actingAs(User::factory()->create());

        return $this->postJson('/api/pedidos', [
            'modelo_id'      => $this->combinacion->modelo_id,
            'sucursal_id'    => $this->combinacion->sucursal_id,
            'cliente_nombre' => 'Juan Pérez',
            'cliente_email'  => 'juan@test.com',
        ])->assertStatus(201)->json('id');
    }

    private function comoAdmin(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function stock(): int
    {
        return DB::table('modelo_sucursal')->where('id', $this->combinacion->id)->value('stock');
    }

    public function test_admin_confirma_un_pedido_pendiente()
    {
        $id = $this->crearPedido();
        $this->comoAdmin();

        $this->patchJson("/api/pedidos/{$id}/estado", ['estado' => 'confirmado'])
            ->assertStatus(200)
            ->assertJsonPath('estado', 'confirmado');

        $this->assertDatabaseHas('pedidos', ['id' => $id, 'estado' => 'confirmado']);
        $this->assertEquals(1, $this->stock());  // la unidad sigue vendida
    }

    public function test_admin_cancela_y_la_unidad_vuelve_al_stock()
    {
        $id = $this->crearPedido();
        $this->comoAdmin();

        $this->patchJson("/api/pedidos/{$id}/estado", ['estado' => 'cancelado'])
            ->assertStatus(200)
            ->assertJsonPath('estado', 'cancelado');

        $this->assertEquals(2, $this->stock());
    }

    public function test_un_pedido_confirmado_ya_no_cambia()
    {
        $id = $this->crearPedido();
        $this->comoAdmin();
        $this->patchJson("/api/pedidos/{$id}/estado", ['estado' => 'confirmado'])->assertStatus(200);

        $this->patchJson("/api/pedidos/{$id}/estado", ['estado' => 'cancelado'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('estado');

        $this->assertDatabaseHas('pedidos', ['id' => $id, 'estado' => 'confirmado']);
        $this->assertEquals(1, $this->stock());  // no se devolvió la unidad
    }

    public function test_un_pedido_cancelado_no_devuelve_stock_dos_veces()
    {
        $id = $this->crearPedido();
        $this->comoAdmin();
        $this->patchJson("/api/pedidos/{$id}/estado", ['estado' => 'cancelado'])->assertStatus(200);

        $this->patchJson("/api/pedidos/{$id}/estado", ['estado' => 'cancelado'])->assertStatus(422);

        $this->assertEquals(2, $this->stock());
    }

    public function test_estado_invalido_responde_422()
    {
        $id = $this->crearPedido();
        $this->comoAdmin();

        // "pendiente" tampoco se acepta: un pedido no puede regresar a pendiente
        foreach (['entregado', 'pendiente', ''] as $estado) {
            $this->patchJson("/api/pedidos/{$id}/estado", ['estado' => $estado])
                ->assertStatus(422)
                ->assertJsonValidationErrors('estado');
        }

        $this->assertDatabaseHas('pedidos', ['id' => $id, 'estado' => 'pendiente']);
    }

    public function test_usuario_sin_admin_responde_403()
    {
        $id = $this->crearPedido();  // crearPedido deja la sesión como usuario normal

        $this->patchJson("/api/pedidos/{$id}/estado", ['estado' => 'confirmado'])
            ->assertStatus(403);

        $this->assertDatabaseHas('pedidos', ['id' => $id, 'estado' => 'pendiente']);
    }

    public function test_sin_token_responde_401()
    {
        $this->patchJson('/api/pedidos/1/estado', ['estado' => 'confirmado'])
            ->assertStatus(401);
    }

    public function test_pedido_inexistente_responde_404()
    {
        $this->comoAdmin();

        $this->patchJson('/api/pedidos/999/estado', ['estado' => 'confirmado'])
            ->assertStatus(404);
    }

    public function test_no_se_puede_hacer_admin_desde_register()
    {
        $this->postJson('/api/register', [
            'name'     => 'Intruso',
            'email'    => 'intruso@test.com',
            'password' => 'password123',
            'is_admin' => true,
        ])->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => 'intruso@test.com', 'is_admin' => false]);
    }
}
