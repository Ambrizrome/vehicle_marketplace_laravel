<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use App\Models\Modelo;
use App\Models\User;
use App\Services\ModeloService;

// Pruebas de las medidas contra inyección SQL y XSS (corren en SQLite en memoria, no tocan larosite)
class SeguridadTest extends TestCase
{
    use RefreshDatabase;  // base limpia en cada test (SQLite en memoria)

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogoSeeder::class);  // mismos datos que en larosite
    }

    // Datos válidos de un pedido con el nombre de cliente indicado
    private function datosPedido(string $clienteNombre): array
    {
        $combinacion = DB::table('modelo_sucursal')->where('stock', '>', 0)->first();

        return [
            'modelo_id'      => $combinacion->modelo_id,
            'sucursal_id'    => $combinacion->sucursal_id,
            'cliente_nombre' => $clienteNombre,
            'cliente_email'  => 'cliente@test.com',
        ];
    }

    // --- Inyección SQL ---

    public function test_eloquent_manda_el_ataque_como_dato_y_no_como_sql()
    {
        $ataque = "x' OR 1=1 --";
        $consulta = Modelo::where('nombre', 'like', "%{$ataque}%");

        // El SQL lleva un ? en lugar del valor; el ataque viaja aparte, en los bindings
        $this->assertStringContainsString('like ?', $consulta->toSql());
        $this->assertStringNotContainsString('OR 1=1', $consulta->toSql());
        $this->assertEquals(["%{$ataque}%"], $consulta->getBindings());
    }

    public function test_eloquent_solo_ya_neutraliza_la_inyeccion_sin_el_middleware()
    {
        // Se llama al Service directo (sin pasar por el middleware): si el ataque funcionara, devolvería los 10 modelos
        $resultado = app(ModeloService::class)->obtenerModelos(['q' => "' OR '1'='1"]);

        $this->assertCount(0, $resultado);
    }

    public function test_ataques_de_inyeccion_sql_responden_422_y_se_registran()
    {
        Log::spy();

        $ataques = [
            "' OR 1=1 --",
            "' or 'a'='a",
            "x' UNION SELECT email, password FROM users",
            "Ferrari'; DROP TABLE pedidos",
            "admin' --",
        ];

        foreach ($ataques as $ataque) {
            $this->getJson('/api/modelos?q=' . urlencode($ataque))
                ->assertStatus(422)
                ->assertJsonValidationErrors('q');
        }

        Log::shouldHaveReceived('warning')->times(count($ataques));
    }

    // --- XSS ---

    public function test_pedido_con_script_responde_422_y_no_crea_nada()
    {
        Sanctum::actingAs(User::factory()->create());
        $datos = $this->datosPedido('<script>alert(1)</script>');
        $stockAntes = DB::table('modelo_sucursal')
            ->where('modelo_id', $datos['modelo_id'])->where('sucursal_id', $datos['sucursal_id'])->value('stock');

        $this->postJson('/api/pedidos', $datos)
            ->assertStatus(422)
            ->assertJsonValidationErrors('cliente_nombre');

        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseHas('modelo_sucursal', [
            'modelo_id' => $datos['modelo_id'], 'sucursal_id' => $datos['sucursal_id'], 'stock' => $stockAntes,
        ]);
    }

    public function test_html_en_cualquier_modulo_responde_422()
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

        $this->postJson('/api/marcas', ['nombre' => '<img src=x onerror=alert(1)>', 'pais_origen' => 'X'])
            ->assertStatus(422)->assertJsonValidationErrors('nombre');

        $this->postJson('/api/sucursales', ['nombre' => 'Centro', 'ciudad' => '<b>CDMX</b>', 'direccion' => 'Av. 1'])
            ->assertStatus(422)->assertJsonValidationErrors('ciudad');

        $this->assertDatabaseMissing('marcas', ['pais_origen' => 'X']);
    }

    public function test_la_salida_json_escapa_el_html_guardado_por_otro_camino()
    {
        // Simula un dato con HTML que entró sin pasar por la API (seeder o la base directo)
        $id = DB::table('marcas')->insertGetId(['nombre' => '<b>Marca</b>', 'pais_origen' => 'X']);

        $response = $this->getJson("/api/marcas/{$id}")->assertStatus(200);

        // Texto esperado: cada < y > como su código unicode (u003C, u003E), igual que lo escapa PHP con JSON_HEX_TAG
        $escapado = substr(json_encode('<b>Marca</b>', JSON_HEX_TAG), 1, -1);

        $this->assertStringContainsString($escapado, $response->getContent());
        $this->assertStringNotContainsString('<b>', $response->getContent());
        $this->assertEquals('<b>Marca</b>', $response->json('nombre'));  // el cliente recibe el texto original
    }

    public function test_las_respuestas_llevan_cabeceras_de_seguridad()
    {
        // Una respuesta normal y un 404 (whereNumber) llevan las mismas cabeceras
        foreach (['/api/marcas', '/api/modelos/abc'] as $ruta) {
            $this->getJson($ruta)
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'")
                ->assertHeader('X-Frame-Options', 'DENY')
                ->assertHeader('Referrer-Policy', 'no-referrer');
        }
    }

    // --- Sin falsos positivos ---

    public function test_texto_normal_con_comillas_y_simbolos_no_se_bloquea()
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/pedidos', $this->datosPedido("Juan O'Brien"))->assertStatus(201);

        $this->getJson('/api/modelos?q=GT')->assertStatus(200)->assertJsonFragment(['nombre' => 'AMG GT']);
        $this->getJson('/api/modelos?q=' . urlencode('5 < 6'))->assertStatus(200);

        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));
        $this->postJson('/api/sucursales', [
            'nombre' => 'Luxury Cars "Sur"', 'ciudad' => 'Puebla', 'direccion' => 'Calle 5 de Mayo #12, Col. Centro',
        ])->assertStatus(201);
    }

    public function test_las_contrasenas_no_se_revisan()
    {
        // Lleva < (parece HTML) y ' -- (parece SQL), pero se guarda hasheada: no se rechaza
        $this->postJson('/api/register', [
            'name'     => 'Ana',
            'email'    => 'ana@test.com',
            'password' => "Ab<x' --9",
        ])->assertStatus(201);
    }
}
