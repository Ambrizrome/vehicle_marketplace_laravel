# Guía: CRUD de Sucursales e Inventario (disponibilidad de vehículos)

**Responsable:** Aaron · **Módulos:** Sucursales e Inventario
**Otros módulos:** Pedidos y Ventas (Carlos, ya terminado) · Marcas y Modelos (Jesús, ver `docs/guia-marcas-modelos.md`)

Esta guía explica cómo funciona la API, qué te toca hacer y cómo hacerlo siguiendo el mismo flujo que ya usa el proyecto. Si usas un agente de IA (Claude Code, Codex, etc.), pásale este archivo como contexto.

---

## 1. Contexto del proyecto

- **Laravel 8, PHP 8.1, MySQL 8.** Se trabaja dentro del devcontainer. La base es `larosite` (servicio `db`).
- **Solo es una API REST.** No hay páginas web: todo se prueba con Postman y con tests. `/` solo devuelve un JSON.
- **Datos de prueba:** `CatalogoSeeder` carga 5 marcas, 10 modelos y 3 sucursales, y pone **cada modelo en cada sucursal** con un stock de 0 a 3, calculado como `(modelo_id + sucursal_id) % 4`.

### Tablas de tu módulo

| Tabla | Columnas | Notas |
|---|---|---|
| `sucursales` | `id`, `nombre`, `ciudad`, `direccion`, `telefono` (nullable) | El modelo `Sucursal` declara `$table = 'sucursales'` |
| `modelo_sucursal` | `id`, `modelo_id`, `sucursal_id`, `stock` (unsigned, nunca negativo) | Tabla pivote = **el inventario**. `unique(modelo_id, sucursal_id)`; ambas llaves con `cascadeOnDelete` |

La relación ya existe en los dos modelos: `Sucursal::modelos()` y `Modelo::sucursales()`, ambas con `->withPivot('stock')`. El stock se lee como `$modelo->pivot->stock`.

Tablas de otros módulos que dependen de las tuyas:

| Tabla | Relación con tu módulo | Consecuencia |
|---|---|---|
| `pedidos` (de Carlos) | `sucursal_id` **sin** cascade | La base de datos **no deja** borrar una sucursal que tiene pedidos (error 500 si no lo controlas) |

---

## 2. Cómo funciona la API que ya existe

### Autenticación con tokens (Sanctum)

1. `POST /api/register` o `POST /api/login` devuelven `{ "user": {...}, "token": "5|AbCd..." }`.
2. Las rutas protegidas se llaman con el header `Authorization: Bearer <token>`. En Postman: pestaña **Authorization** → **Bearer Token**.
3. `POST /api/logout` invalida ese token.

### Roles: usuario normal y admin

- La tabla `users` tiene la columna `is_admin`. **No** está en `$fillable`, así que nadie puede hacerse admin desde `/register`.
- El admin se crea con `AdminSeeder`, que toma `ADMIN_EMAIL` y `ADMIN_PASSWORD` del `.env`.
- En `AuthServiceProvider` existe el permiso `admin`. Las rutas lo usan con `middleware('can:admin')`.

### Cómo usa Pedidos el inventario (importante para ti)

- **Crear un pedido** (`POST /api/pedidos`): dentro de una transacción, `PedidoService` bloquea la fila de `modelo_sucursal` con `lockForUpdate()`. Si `stock < 1`, responde 422; si hay stock, **resta 1** con `updateExistingPivot`.
- **Cancelar un pedido** (`PATCH /api/pedidos/{id}/estado` con `cancelado`): **suma 1** al stock de esa combinación de modelo y sucursal.
- **Confirmar un pedido** (convertirlo en venta) no toca el stock, porque la unidad ya se descontó al crearlo.

### Respuestas de error (todas en JSON)

| Código | Cuándo |
|---|---|
| 401 | Sin token o con un token inválido |
| 403 | Con token, pero el usuario no es admin |
| 404 | El id no existe (`findOrFail`) o no es un número (`whereNumber`) |
| 422 | Falló la validación (FormRequest) o una regla de negocio (`ValidationException`) |
| 429 | Más de 60 peticiones por minuto |

### Rutas actuales

| Método | Ruta | Acceso |
|---|---|---|
| POST | `/api/register`, `/api/login` | Público |
| GET | `/api/marcas`, `/api/marcas/{id}` | Público |
| GET | `/api/modelos`, `/api/modelos/{id}` | Público (`{id}` muestra las sucursales donde está el modelo, con su stock) |
| GET | `/api/sucursales`, `/api/sucursales/{id}` | Público (`{id}` muestra los modelos de la sucursal, con su stock) |
| GET / POST | `/api/user`, `/api/logout` | Token |
| POST | `/api/pedidos` | Token (crea un pedido y descuenta 1 unidad de stock) |
| GET | `/api/pedidos` | Admin |
| PATCH | `/api/pedidos/{id}/estado` | Admin (`confirmado` o `cancelado`; al cancelar, la unidad vuelve al stock) |
| GET | `/api/ventas` | Admin (lista los pedidos confirmados) |

---

## 3. El flujo de la API (obligatorio)

Cada endpoint pasa por estas capas, y cada capa hace **solo** lo suyo:

```
Route (routes/api.php)            → URL, método y middleware (token, admin)
  → FormRequest (app/Http/Requests) → valida los datos de entrada (422 si fallan)
    → Controller (app/Http/Controllers) → recibe la petición y responde JSON; sin lógica
      → Service (app/Services)       → lógica y reglas de negocio; aquí se consulta la base
        → Model (app/Models)          → tabla, $fillable, $hidden, $casts y relaciones
```

**Reglas del proyecto:**
- El Controller no consulta la base ni tiene `if` de negocio: llama al Service y devuelve `response()->json(...)`.
- Los errores de negocio se lanzan desde el Service con `ValidationException::withMessages([...])`, y Laravel responde 422.
- Usa `findOrFail` para buscar por id (responde 404 solo) y agrega `->whereNumber('id')` en las rutas con `{id}`.
- Usa siempre Eloquent o el query builder, **nunca** SQL armado concatenando texto. Así se evita la inyección SQL.
- **Si tocas el stock, hazlo dentro de `DB::transaction(...)` y bloquea la fila con `lockForUpdate()`**, igual que `PedidoService::crearPedido()`. Si no, un pedido y un ajuste de inventario que lleguen al mismo tiempo pueden pisarse.
- Escribe comentarios cortos en español y nombres de métodos en español (`obtenerSucursales`, `crearSucursal`, `actualizarStock`, `quitarModelo`).
- Separa el inventario en su propio `InventarioController` + `InventarioService`, así cada módulo queda en sus propios archivos.

---

## 4. Lo que te toca

**Regla de acceso:** consultar sucursales y disponibilidad es público. **Crear, editar y borrar, incluido el inventario, es solo para el admin.**

### Sucursales (`SucursalController` + `SucursalService`)

| Método | Ruta | Body | Respuesta |
|---|---|---|---|
| POST | `/api/sucursales` | `nombre`, `ciudad`, `direccion`, `telefono` (opcional) | 201 con la sucursal |
| PUT | `/api/sucursales/{id}` | Los mismos campos | 200 con la sucursal |
| DELETE | `/api/sucursales/{id}` | — | 200 `{ "message": "Sucursal eliminada" }` |

### Inventario (`InventarioController` + `InventarioService`)

| Método | Ruta | Acceso | Body | Qué hace |
|---|---|---|---|---|
| GET | `/api/sucursales/{id}/inventario` | Público | — | Modelos de la sucursal con su marca y su stock |
| POST | `/api/sucursales/{id}/inventario` | Admin | `modelo_id`, `stock` | Da de alta un modelo en la sucursal (`attach`). Responde 201 |
| PUT | `/api/sucursales/{id}/inventario/{modeloId}` | Admin | `stock` | Fija el stock, por ejemplo al reponer unidades o ajustar el conteo |
| DELETE | `/api/sucursales/{id}/inventario/{modeloId}` | Admin | — | Quita el modelo de la sucursal (`detach`) |

Opcional (disponibilidad): `GET /api/disponibilidad?modelo_id=1`, que devuelve las sucursales donde ese modelo tiene `stock > 0`.

### Validaciones (en el FormRequest)

| Campo | Reglas |
|---|---|
| Sucursal `nombre` | `required\|string\|max:100` |
| Sucursal `ciudad` | `required\|string\|max:100` |
| Sucursal `direccion` | `required\|string\|max:255` |
| Sucursal `telefono` | `nullable\|string\|max:20` |
| Inventario `modelo_id` | `required\|exists:modelos,id` |
| Inventario `stock` | `required\|integer\|min:0` (la columna no acepta negativos) |

### Reglas de negocio (en el Service)

1. **No borrar una sucursal que tiene pedidos.** Responde 422 con un mensaje. Sin esta regla, la base de datos lanza un error 500. Una sucursal sin pedidos sí se puede borrar, y su inventario se borra solo por el cascade.
2. **Dar de alta un modelo que ya está en la sucursal** responde 422, por ejemplo con "Este modelo ya está en el inventario de esta sucursal". Para cambiar sus unidades se usa el `PUT`.
3. **Cambiar el stock de un modelo que no está en la sucursal** responde 422.
4. **No quitar un modelo de una sucursal si tiene pedidos `pendiente`** de esa combinación. Responde 422. Si se quitara, al cancelar ese pedido la unidad no tendría a dónde volver y se perdería sin avisar.
5. **No cambies** el nombre de la tabla `modelo_sucursal`, de la columna `stock` ni de las relaciones `Sucursal::modelos()` y `Modelo::sucursales()`: Pedidos depende de ellas.

---

## 5. Ejemplo completo: `PUT /api/sucursales/{id}/inventario/{modeloId}`

Sigue este mismo patrón para el resto de los endpoints.

**1. FormRequest:** `app/Http/Requests/UpdateStockRequest.php`
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Valida los datos de PUT /api/sucursales/{id}/inventario/{modeloId} (si falla, responde 422)
class UpdateStockRequest extends FormRequest
{
    // Que sea admin ya se revisa en la ruta (can:admin)
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'stock' => 'required|integer|min:0',
        ];
    }
}
```

**2. Service:** `app/Services/InventarioService.php`
```php
<?php

namespace App\Services;

use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioService
{
    // Fija las unidades de un modelo en una sucursal; 422 si el modelo no está en esa sucursal
    public function actualizarStock($sucursalId, $modeloId, int $stock): Sucursal
    {
        return DB::transaction(function () use ($sucursalId, $modeloId, $stock) {
            $sucursal = Sucursal::findOrFail($sucursalId);

            // lockForUpdate: si al mismo tiempo entra un pedido de este modelo, espera a que terminemos
            $modelo = $sucursal->modelos()->where('modelos.id', $modeloId)->lockForUpdate()->first();

            if (!$modelo) {
                throw ValidationException::withMessages(['modelo_id' => 'Este modelo no está en el inventario de esta sucursal']);
            }

            $sucursal->modelos()->updateExistingPivot($modeloId, ['stock' => $stock]);

            return $sucursal->load('modelos.marca');
        });
    }
}
```

**3. Controller:** `app/Http/Controllers/InventarioController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStockRequest;
use App\Services\InventarioService;

class InventarioController extends Controller
{
    protected $inventarioService;

    public function __construct(InventarioService $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    // PUT /api/sucursales/{id}/inventario/{modeloId} -> fija el stock de un modelo en la sucursal (solo admin)
    public function update(UpdateStockRequest $request, $id, $modeloId)
    {
        return response()->json($this->inventarioService->actualizarStock($id, $modeloId, $request->validated()['stock']));
    }
}
```

**4. Rutas:** en `routes/api.php`, en **tu propio bloque** debajo de las rutas públicas de sucursales. Así no chocas con los bloques de Jesús ni de Carlos al hacer merge.
```php
// Inventario de una sucursal (público)
Route::get('/sucursales/{id}/inventario', [InventarioController::class, 'index'])->whereNumber('id');

// Sucursales e inventario: crear, editar y borrar (solo admin)
Route::middleware(['auth:sanctum', 'can:admin'])->group(function () {
    Route::post('/sucursales', [SucursalController::class, 'store']);
    Route::put('/sucursales/{id}', [SucursalController::class, 'update'])->whereNumber('id');
    Route::delete('/sucursales/{id}', [SucursalController::class, 'destroy'])->whereNumber('id');

    Route::post('/sucursales/{id}/inventario', [InventarioController::class, 'store'])->whereNumber('id');
    Route::put('/sucursales/{id}/inventario/{modeloId}', [InventarioController::class, 'update'])->whereNumber(['id', 'modeloId']);
    Route::delete('/sucursales/{id}/inventario/{modeloId}', [InventarioController::class, 'destroy'])->whereNumber(['id', 'modeloId']);
});
```

**Pistas para los otros métodos:**
- **Alta:** `$sucursal->modelos()->attach($modeloId, ['stock' => $stock])`. Antes revisa si ya existe con `$sucursal->modelos()->where('modelos.id', $modeloId)->exists()`.
- **Quitar:** `$sucursal->modelos()->detach($modeloId)`. Antes revisa que no haya pedidos pendientes: `Pedido::where('sucursal_id', $id)->where('modelo_id', $modeloId)->where('estado', 'pendiente')->exists()`.
- **Borrar una sucursal:** antes revisa que no tenga pedidos con `$sucursal->pedidos()->exists()`.

---

## 6. Tests

Los tests corren en **SQLite en memoria** (configurado en `phpunit.xml`), así que nunca tocan `larosite`. Toma como ejemplo `tests/Feature/PedidoTest.php` y `tests/Feature/PedidoEstadoTest.php`.

```php
class InventarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogoSeeder::class);  // mismos datos que en larosite
    }

    public function test_admin_fija_el_stock()
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));  // simula el token de admin
        $combinacion = DB::table('modelo_sucursal')->first();

        $this->putJson("/api/sucursales/{$combinacion->sucursal_id}/inventario/{$combinacion->modelo_id}", ['stock' => 10])
            ->assertStatus(200);

        $this->assertDatabaseHas('modelo_sucursal', ['id' => $combinacion->id, 'stock' => 10]);
    }
}
```

**Casos mínimos por endpoint:**
- El admin lo hace bien: 201 o 200, y el cambio queda en la base.
- Un usuario normal recibe 403: `Sanctum::actingAs(User::factory()->create())`.
- Sin token recibe 401: no llames a `actingAs`.
- Datos inválidos, por ejemplo `stock: -1` o un `modelo_id` que no existe: 422 con `assertJsonValidationErrors('campo')`.
- Un id inexistente: 404.
- Reglas de negocio: dar de alta un modelo repetido, quitar un modelo con pedidos pendientes y borrar una sucursal con pedidos responden 422, y no cambia nada en la base.
- **Integración con pedidos:** después de reponer stock con el `PUT`, un `POST /api/pedidos` que antes daba 422 por falta de stock ahora da 201.

Para crear un pedido dentro de un test, mira `crearPedido()` en `tests/Feature/PedidoEstadoTest.php`.

```bash
XDEBUG_MODE=off php artisan test                            # todos (deben seguir pasando los de pedidos)
XDEBUG_MODE=off php artisan test --filter=InventarioTest    # solo los tuyos
```

---

## 7. Puesta en marcha y prueba en Postman

```bash
git pull
php artisan migrate                              # agrega is_admin a users si no lo tienes
# En tu .env: ADMIN_EMAIL=admin@larosite.test y ADMIN_PASSWORD=<tu contraseña>
php artisan db:seed --class=AdminSeeder          # solo esta clase; db:seed solo duplicaría el catálogo
XDEBUG_MODE=off php artisan serve --host=0.0.0.0 --port=8000
```

En Postman:
1. Crea un Environment con `base_url = http://localhost:8000/api` y una variable `token`.
2. Haz `POST {{base_url}}/login` con el email y la contraseña del admin, y guarda el token. Para que se guarde solo, pon esto en la pestaña *Scripts* → *Post-response*: `pm.environment.set("token", pm.response.json().token);`
3. En cada petición de admin: **Authorization** → **Bearer Token** → `{{token}}`.
4. Body → raw → JSON, por ejemplo: `{ "stock": 5 }`.

---

## 8. Qué no tocar

- **No cambies** la tabla `modelo_sucursal`, la columna `stock` ni las relaciones `Sucursal::modelos()`, `Modelo::sucursales()` y `Sucursal::pedidos()`: Pedidos las usa.
- **No modifiques** los archivos de Pedidos, Ventas ni Auth (`PedidoService`, `PedidoController`, `VentaService`, `AuthService`, `AuthServiceProvider`, `Handler`). Si necesitas algo de ellos, avísale a Carlos.
- **Nunca** uses `RefreshDatabase` ni `migrate:fresh` apuntando a `larosite`: borra todos los datos.
- **No subas** tu `.env` (ya está en `.gitignore`).

---

## 9. Checklist de entrega

- [ ] `POST`, `PUT` y `DELETE` de sucursales con FormRequest, Service y Controller.
- [ ] Inventario: consultar, dar de alta, fijar el stock y quitar, con `InventarioController` + `InventarioService`.
- [ ] Cambios de stock dentro de `DB::transaction` + `lockForUpdate`.
- [ ] Rutas de escritura dentro de `auth:sanctum` + `can:admin`; las consultas siguen públicas.
- [ ] Reglas: borrar una sucursal con pedidos, dar de alta un modelo repetido y quitar un modelo con pedidos pendientes responden 422.
- [ ] Tests de cada endpoint (admin, 403, 401, 422 y 404), incluida la integración con pedidos, y `php artisan test` en verde.
- [ ] Probado en Postman.
- [ ] Commits pequeños con mensajes claros, por ejemplo `feat(api): CRUD de sucursales (solo admin)`, y `git pull` antes de hacer push.
