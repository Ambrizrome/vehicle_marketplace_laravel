<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use App\Models\Sucursal;
use App\Models\User;

// Pruebas de POST y GET /api/pedidos (corren en SQLite en memoria, no tocan larosite)
class PedidoTest extends TestCase
{
    use RefreshDatabase;  // base limpia en cada test (SQLite en memoria)

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogoSeeder::class);  // mismos datos que en larosite
    }

    // Busca una combinación modelo-sucursal con ese stock, para no depender de ids fijos
    private function combinacionConStock(int $stock)
    {
        return DB::table('modelo_sucursal')->where('stock', $stock)->first();
    }

    // Datos válidos de un pedido para esa combinación
    private function datosPedido($combinacion): array
    {
        return [
            'modelo_id'      => $combinacion->modelo_id,
            'sucursal_id'    => $combinacion->sucursal_id,
            'cliente_nombre' => 'Juan Pérez',
            'cliente_email'  => 'juan@test.com',
        ];
    }

    public function test_crea_el_pedido_y_descuenta_una_unidad()
    {
        Sanctum::actingAs(User::factory()->create());  // simula el token
        $combinacion = $this->combinacionConStock(2);

        $this->postJson('/api/pedidos', $this->datosPedido($combinacion))
            ->assertStatus(201)
            ->assertJsonPath('estado', 'pendiente');

        $this->assertDatabaseHas('pedidos', ['modelo_id' => $combinacion->modelo_id, 'sucursal_id' => $combinacion->sucursal_id]);
        $this->assertDatabaseHas('modelo_sucursal', ['id' => $combinacion->id, 'stock' => 1]);
    }

    public function test_sin_token_responde_401()
    {
        $this->postJson('/api/pedidos', $this->datosPedido($this->combinacionConStock(2)))
            ->assertStatus(401);

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_datos_faltantes_responden_422()
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/pedidos', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['modelo_id', 'sucursal_id', 'cliente_nombre', 'cliente_email']);
    }

    public function test_modelo_inexistente_responde_422()
    {
        Sanctum::actingAs(User::factory()->create());
        $datos = $this->datosPedido($this->combinacionConStock(2));
        $datos['modelo_id'] = 999;

        $this->postJson('/api/pedidos', $datos)
            ->assertStatus(422)
            ->assertJsonValidationErrors('modelo_id');
    }

    public function test_sin_stock_responde_422_y_no_crea_el_pedido()
    {
        Sanctum::actingAs(User::factory()->create());
        $combinacion = $this->combinacionConStock(0);

        $this->postJson('/api/pedidos', $this->datosPedido($combinacion))
            ->assertStatus(422)
            ->assertJsonValidationErrors('modelo_id');

        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseHas('modelo_sucursal', ['id' => $combinacion->id, 'stock' => 0]);
    }

    public function test_modelo_que_no_se_vende_en_la_sucursal_responde_422()
    {
        Sanctum::actingAs(User::factory()->create());
        $combinacion = $this->combinacionConStock(2);

        // El seeder pone todos los modelos en todas las sucursales, así que quitamos uno
        Sucursal::find($combinacion->sucursal_id)->modelos()->detach($combinacion->modelo_id);

        $this->postJson('/api/pedidos', $this->datosPedido($combinacion))
            ->assertStatus(422)
            ->assertJsonValidationErrors('modelo_id');
    }

    public function test_no_vende_mas_unidades_de_las_que_hay()
    {
        Sanctum::actingAs(User::factory()->create());
        $combinacion = $this->combinacionConStock(1);

        $this->postJson('/api/pedidos', $this->datosPedido($combinacion))->assertStatus(201);
        $this->postJson('/api/pedidos', $this->datosPedido($combinacion))->assertStatus(422);

        $this->assertDatabaseCount('pedidos', 1);
        $this->assertDatabaseHas('modelo_sucursal', ['id' => $combinacion->id, 'stock' => 0]);
    }

    public function test_admin_ve_la_lista_de_pedidos()
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/pedidos', $this->datosPedido($this->combinacionConStock(2)))->assertStatus(201);

        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

        $this->getJson('/api/pedidos')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_usuario_sin_admin_no_ve_la_lista_de_pedidos()
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/pedidos')->assertStatus(403);
    }

    public function test_sin_token_y_sin_header_accept_responde_401_en_json()
    {
        // get() en vez de getJson(): simula Postman sin "Accept: application/json"
        $this->get('/api/pedidos')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }
}
