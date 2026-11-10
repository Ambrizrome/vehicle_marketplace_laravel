# Guía: CRUD de Marcas y Modelos (catálogo)

**Responsable:** Jesús · **Módulos:** Marcas y Modelos
**Otros módulos:** Pedidos y Ventas (Carlos, ya terminado) · Sucursales e Inventario (Aaron, ver `docs/guia-sucursales-inventario.md`)

Esta guía explica cómo funciona la API, qué te toca hacer y cómo hacerlo siguiendo el mismo flujo que ya usa el proyecto. Si usas un agente de IA (Claude Code, Codex, etc.), pásale este archivo como contexto.

---

## 1. Contexto del proyecto

- **Laravel 8, PHP 8.1, MySQL 8.** Se trabaja dentro del devcontainer. La base es `larosite` (servicio `db`).
- **Solo es una API REST.** No hay páginas web: todo se prueba con Postman y con tests. `/` solo devuelve un JSON.
- **Datos de prueba:** `CatalogoSeeder` carga 5 marcas, 10 modelos, 3 sucursales y el stock de cada modelo en cada sucursal.

### Tablas de tu módulo

| Tabla | Columnas | Notas |
|---|---|---|
| `marcas` | `id`, `nombre` (único), `pais_origen` | |
| `modelos` | `id`, `marca_id`, `nombre`, `anio`, `tipo`, `precio` (decimal) | `marca_id` → `marcas` con `cascadeOnDelete` |

Tablas de otros módulos que dependen de las tuyas:

| Tabla | Relación con tu módulo | Consecuencia |
|---|---|---|
| `modelo_sucursal` (stock, de Aaron) | `modelo_id` con `cascadeOnDelete` | Borrar un modelo borra su stock en todas las sucursales |
| `pedidos` (de Carlos) | `modelo_id` **sin** cascade | La base de datos **no deja** borrar un modelo que tiene pedidos (error 500 si no lo controlas) |

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
| GET | `/api/modelos`, `/api/modelos/{id}` | Público |
| GET | `/api/sucursales`, `/api/sucursales/{id}` | Público |
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
- Si una operación escribe en más de una tabla, envuélvela en `DB::transaction(...)`.
- Escribe comentarios cortos en español y nombres de métodos en español (`obtenerMarcas`, `crearMarca`, `actualizarMarca`, `eliminarMarca`).
- En `index`, `show` y `store`, toma como modelo el código que ya existe en `PedidoController`, `PedidoService` y `StorePedidoRequest`.

---

## 4. Lo que te toca

**Regla de acceso:** los `GET` siguen públicos (el catálogo se puede consultar sin cuenta). **Crear, editar y borrar es solo para el admin.**

### Marcas

| Método | Ruta | Body | Respuesta |
|---|---|---|---|
| POST | `/api/marcas` | `nombre`, `pais_origen` | 201 con la marca |
| PUT | `/api/marcas/{id}` | `nombre`, `pais_origen` | 200 con la marca |
| DELETE | `/api/marcas/{id}` | — | 200 `{ "message": "Marca eliminada" }` |
| GET | `/api/marcas?q=fer` | — | Búsqueda por nombre (ver la sección 6) |

### Modelos

| Método | Ruta | Body | Respuesta |
|---|---|---|---|
| POST | `/api/modelos` | `marca_id`, `nombre`, `anio`, `tipo`, `precio` | 201 con el modelo |
| PUT | `/api/modelos/{id}` | Los mismos campos | 200 con el modelo |
| DELETE | `/api/modelos/{id}` | — | 200 `{ "message": "Modelo eliminado" }` |
| GET | `/api/modelos?marca_id=1&tipo=SUV&anio=2023&precio_min=100000&precio_max=300000&q=gt` | — | Búsqueda con filtros opcionales |

### Validaciones (en el FormRequest)

| Campo | Reglas |
|---|---|
| Marca `nombre` | `required\|string\|max:100` y único en `marcas` (al editar, ignorando la propia marca) |
| Marca `pais_origen` | `required\|string\|max:100` |
| Modelo `marca_id` | `required\|exists:marcas,id` |
| Modelo `nombre` | `required\|string\|max:100` |
| Modelo `anio` | `required\|integer\|between:1900,2100` |
| Modelo `tipo` | `required\|string\|max:50` |
| Modelo `precio` | `required\|numeric\|min:0` |
| Filtros de búsqueda | Todos `nullable`: `marca_id` integer, `anio` integer, `precio_min` y `precio_max` numeric, `tipo` y `q` string con `max:100` |

### Reglas de negocio (en el Service)

1. **No borrar un modelo que tiene pedidos.** Responde 422 con un mensaje. Sin esta regla, la base de datos lanza un error 500.
2. **No borrar una marca que tiene modelos.** Responde 422 con un mensaje. Si no lo controlas, el cascade borraría todos sus modelos y su stock sin avisar.
3. **Cambiar el precio de un modelo no afecta a los pedidos viejos:** cada pedido guarda su propio `total`. No hay que hacer nada extra.
4. **Al borrar un modelo sin pedidos,** su stock en `modelo_sucursal` se borra solo por el cascade. Avísale a Aaron, porque es su tabla.

---

## 5. Ejemplo completo: `POST /api/marcas`

Sigue este mismo patrón para el resto de los endpoints.

**1. FormRequest:** `app/Http/Requests/StoreMarcaRequest.php`
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Valida los datos de POST y PUT /api/marcas (si falla, responde 422)
class StoreMarcaRequest extends FormRequest
{
    // Que sea admin ya se revisa en la ruta (can:admin)
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // ignore(): al editar, el nombre actual de la propia marca no cuenta como repetido
            'nombre'      => ['required', 'string', 'max:100', Rule::unique('marcas', 'nombre')->ignore($this->route('id'))],
            'pais_origen' => 'required|string|max:100',
        ];
    }
}
```

**2. Service:** métodos nuevos en `app/Services/MarcaService.php`. Agrega arriba `use Illuminate\Validation\ValidationException;`.
```php
// Crea una marca con los datos ya validados
public function crearMarca(array $datos): Marca
{
    return Marca::create($datos);
}

// Borra una marca; si tiene modelos responde 422 para no borrarlos en cascada
public function eliminarMarca($id): void
{
    $marca = Marca::findOrFail($id);

    if ($marca->modelos()->exists()) {
        throw ValidationException::withMessages(['marca' => 'No se puede borrar: la marca tiene modelos']);
    }

    $marca->delete();
}
```

**3. Controller:** métodos nuevos en `app/Http/Controllers/MarcaController.php`
```php
// POST /api/marcas -> crea una marca (solo admin)
public function store(StoreMarcaRequest $request)
{
    return response()->json($this->marcaService->crearMarca($request->validated()), 201);
}

// DELETE /api/marcas/{id} -> borra una marca sin modelos (solo admin)
public function destroy($id)
{
    $this->marcaService->eliminarMarca($id);

    return response()->json(['message' => 'Marca eliminada']);
}
```

**4. Rutas:** en `routes/api.php`, en **tu propio bloque** debajo de las rutas públicas de marcas y modelos. Así no chocas con los bloques de Aaron ni de Carlos al hacer merge.
```php
// Marcas y modelos: crear, editar y borrar (solo admin)
Route::middleware(['auth:sanctum', 'can:admin'])->group(function () {
    Route::post('/marcas', [MarcaController::class, 'store']);
    Route::put('/marcas/{id}', [MarcaController::class, 'update'])->whereNumber('id');
    Route::delete('/marcas/{id}', [MarcaController::class, 'destroy'])->whereNumber('id');
    // ... lo mismo para /modelos
});
```

---

## 6. Búsquedas y filtros

Valida los filtros con un FormRequest (por ejemplo `BuscarModelosRequest`) y aplícalos en el Service con `when()`, que agrega el `where` solo si el filtro viene en la petición:

```php
// Lista los modelos con su marca; cada filtro se aplica solo si viene en la URL
public function obtenerModelos(array $filtros): Collection
{
    return Modelo::with('marca')
        ->when($filtros['marca_id'] ?? null, fn ($query, $marcaId) => $query->where('marca_id', $marcaId))
        ->when($filtros['tipo'] ?? null, fn ($query, $tipo) => $query->where('tipo', $tipo))
        ->when($filtros['anio'] ?? null, fn ($query, $anio) => $query->where('anio', $anio))
        ->when($filtros['precio_min'] ?? null, fn ($query, $min) => $query->where('precio', '>=', $min))
        ->when($filtros['precio_max'] ?? null, fn ($query, $max) => $query->where('precio', '<=', $max))
        ->when($filtros['q'] ?? null, fn ($query, $texto) => $query->where('nombre', 'like', "%{$texto}%"))
        ->get();
}
```

El `like` con `"%{$texto}%"` es seguro: Eloquent envía `$texto` como parámetro, no como parte del SQL. En el controller pasa `$request->validated()`.

---

## 7. Tests

Los tests corren en **SQLite en memoria** (configurado en `phpunit.xml`), así que nunca tocan `larosite`. Toma como ejemplo `tests/Feature/PedidoTest.php`.

```php
class MarcaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogoSeeder::class);  // mismos datos que en larosite
    }

    public function test_admin_crea_una_marca()
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));  // simula el token de admin

        $this->postJson('/api/marcas', ['nombre' => 'Audi', 'pais_origen' => 'Alemania'])
            ->assertStatus(201)
            ->assertJsonPath('nombre', 'Audi');

        $this->assertDatabaseHas('marcas', ['nombre' => 'Audi']);
    }
}
```

**Casos mínimos por endpoint:**
- El admin lo hace bien: 201 o 200, y el cambio queda en la base.
- Un usuario normal recibe 403: `Sanctum::actingAs(User::factory()->create())`.
- Sin token recibe 401: no llames a `actingAs`.
- Datos inválidos o un nombre repetido: 422 con `assertJsonValidationErrors('campo')`.
- Un id inexistente: 404.
- Borrar un modelo con pedidos o una marca con modelos: 422, y el registro sigue en la base.
- Búsquedas: cada filtro devuelve solo lo que corresponde.

```bash
XDEBUG_MODE=off php artisan test                       # todos (deben seguir pasando los de pedidos)
XDEBUG_MODE=off php artisan test --filter=MarcaTest    # solo los tuyos
```

---

## 8. Puesta en marcha y prueba en Postman

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
4. Body → raw → JSON, por ejemplo: `{ "nombre": "Audi", "pais_origen": "Alemania" }`.

---

## 9. Qué no tocar

- **No cambies** las relaciones `Modelo::sucursales()`, `Modelo::pedidos()` ni la columna `precio`: Pedidos las usa para crear pedidos y calcular el total.
- **No modifiques** los archivos de Pedidos, Ventas ni Auth (`PedidoService`, `PedidoController`, `VentaService`, `AuthService`, `AuthServiceProvider`, `Handler`). Si necesitas algo de ellos, avísale a Carlos.
- **Nunca** uses `RefreshDatabase` ni `migrate:fresh` apuntando a `larosite`: borra todos los datos.
- **No subas** tu `.env` (ya está en `.gitignore`).

---

## 10. Checklist de entrega

- [ ] `POST`, `PUT` y `DELETE` de marcas y modelos con FormRequest, Service y Controller.
- [ ] Rutas de escritura dentro de `auth:sanctum` + `can:admin`; los `GET` siguen públicos.
- [ ] Búsqueda de modelos con filtros, y de marcas por nombre.
- [ ] Reglas de borrado: un modelo con pedidos o una marca con modelos responden 422.
- [ ] Tests de cada endpoint (admin, 403, 401, 422 y 404), y `php artisan test` en verde.
- [ ] Probado en Postman.
- [ ] Commits pequeños con mensajes claros, por ejemplo `feat(api): CRUD de marcas (solo admin)`, y `git pull` antes de hacer push.
