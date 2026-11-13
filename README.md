# Catálogo de Autos API — Backend II

API REST hecha con Laravel para la clase de Backend II en CESUN Universidad.

## ¿Qué es este proyecto?

Es el **backend** de una agencia de autos con varias sucursales. Permite consultar el catálogo (marcas, modelos y en qué sucursal hay unidades), hacer pedidos y llevar el control de ventas e inventario.

Es una **API**: no tiene páginas web. Recibe peticiones HTTP y responde con datos en formato **JSON**. Para probarla se usa **Postman** (o cualquier cliente HTTP).

## ¿Qué se puede hacer?

| Módulo | Qué hace |
|---|---|
| **Usuarios** | Registrarse, iniciar sesión (recibes un token) y cerrar sesión. |
| **Marcas** | Consultar marcas y buscarlas por nombre. El admin las crea, edita y borra. |
| **Modelos** (los productos) | Consultar modelos y filtrarlos por marca, tipo, año, precio o nombre. El admin los crea, edita y borra. |
| **Sucursales** (los vendedores) | Consultar sucursales. El admin las crea, edita y borra. |
| **Inventario** | Ver qué modelos tiene cada sucursal y cuántas unidades (stock). El admin agrega modelos, cambia el stock o los quita. |
| **Pedidos** | Un cliente pide un modelo en una sucursal y se descuenta una unidad del stock. El admin confirma o cancela el pedido. |
| **Ventas** | Lista de pedidos confirmados. |

## ¿Cómo funciona por dentro?

Cada petición pasa por varias **capas**, y cada capa tiene un solo trabajo:

```
Postman ─► Ruta ─► Validación ─► Controller ─► Service ─► Model ─► Base de datos
   ▲                                                                   │
   └──────────────────────── respuesta en JSON ◄───────────────────────┘
```

| Capa | Qué hace | Dónde está | En un restaurante sería... |
|---|---|---|---|
| **Ruta** | Decide qué código atiende cada URL y si pide token o permiso de admin. | `routes/api.php` | El menú |
| **Validación** (FormRequest) | Revisa que los datos que llegan estén completos y bien escritos. | `app/Http/Requests/` | El mesero que revisa tu orden |
| **Controller** | Recibe la petición, llama al Service y devuelve la respuesta. | `app/Http/Controllers/` | El mesero que lleva la orden a la cocina |
| **Service** | Aplica las reglas del negocio: ¿hay stock?, ¿se puede cancelar? | `app/Services/` | El cocinero |
| **Model** | Representa una tabla y sus relaciones; lee y guarda en la base. | `app/Models/` | La despensa |

Separar así el código permite que el controller sea corto, que las reglas se puedan probar por separado y que cada integrante del equipo trabaje en su módulo sin chocar con los demás.

### Ejemplo: crear un pedido

1. **Ruta:** `POST /api/pedidos` pide token. Sin token responde 401.
2. **Validación:** `StorePedidoRequest` revisa que el modelo y la sucursal existan, que el nombre no esté vacío y que el email sea válido. Si algo falla responde 422 con la lista de errores.
3. **Controller:** `PedidoController` le pasa los datos ya validados al Service.
4. **Service:** `PedidoService` revisa que esa sucursal venda el modelo y que tenga stock, crea el pedido y descuenta una unidad. Todo esto pasa **en una transacción**: o se hace todo, o no se hace nada (nunca queda un pedido sin descontar el stock).
5. **Model:** `Pedido` guarda el registro en la tabla `pedidos`.
6. **Respuesta:** 201 con el pedido creado en JSON.

## Las tablas

```
marcas ──1:N── modelos ──N:N── sucursales
                  │                │
                  └─── pedidos ────┘
```

(1:N = uno a muchos, N:N = muchos a muchos)

- Una **marca** tiene muchos **modelos**.
- Un **modelo** puede estar en varias **sucursales**, y una sucursal tiene varios modelos. La tabla `modelo_sucursal` une las dos y guarda el **stock** (cuántas unidades hay de ese modelo en esa sucursal).
- Un **pedido** es de un modelo en una sucursal, y guarda el nombre y email del cliente, el total y el estado.

## Usuarios y permisos

La API usa **tokens** (con Laravel Sanctum). Un token es como la pulsera de un evento: te la dan al entrar (`login`) y la muestras en cada petición en la cabecera `Authorization: Bearer <token>`.

| Quién | Qué puede hacer |
|---|---|
| **Visitante** (sin token) | Consultar marcas, modelos, sucursales e inventario. |
| **Cliente** (con token) | Lo anterior, y además crear pedidos. |
| **Admin** (token de un usuario admin) | Todo: ver pedidos, confirmarlos o cancelarlos, ver ventas y administrar marcas, modelos, sucursales e inventario. |

Cualquiera puede registrarse, pero siempre como cliente. El admin no se puede crear desde la API: lo crea `AdminSeeder` con el `ADMIN_EMAIL` y el `ADMIN_PASSWORD` del archivo `.env`.

## Ciclo de vida de un pedido

```
               ┌──► confirmado   (cuenta como venta)
pendiente ─────┤
               └──► cancelado    (la unidad regresa al stock)
```

- Al crearse, el pedido queda **pendiente** y la sucursal tiene una unidad menos.
- El total es el precio del modelo en el momento del pedido.
- Solo el admin puede confirmarlo o cancelarlo, y solo mientras esté pendiente. Un pedido confirmado o cancelado ya no cambia.
- Si dos clientes piden el último auto al mismo tiempo, solo uno lo consigue: la base de datos bloquea esa fila hasta que el primer pedido termina.

## Seguridad

- **Validación de datos:** todo lo que se envía pasa por reglas antes de llegar a la base: campos obligatorios, formatos (email, números), que los ids existan y largos máximos. Si algo no cumple, responde 422 y explica qué campo está mal.
- **Tokens y permisos:** sin token responde 401, y con token pero sin ser admin responde 403.
- **Límite de peticiones:** máximo 60 por minuto por usuario (o por IP). Después responde 429. Así nadie satura la API ni prueba contraseñas sin parar.
- **Inyección SQL** (escribir pedazos de SQL en un campo para engañar a la base):
  - La protección principal es Eloquent, que manda la consulta y los datos **por separado**. Lo que escribe el usuario siempre se trata como texto, nunca como código.
  - Además, un filtro rechaza textos con patrones típicos de ataque, como `' OR 1=1 --`, y deja un registro en `storage/logs/laravel.log`.
- **XSS** (meter HTML o JavaScript en un dato para que se ejecute en el navegador de otra persona):
  - La API rechaza HTML en los campos (enviar `<script>` responde 422).
  - En las respuestas, los caracteres especiales como `<` y `>` salen escapados.
  - Se envían cabeceras que le prohíben al navegador ejecutar el contenido.
- **Contraseñas:** se guardan como hash (no se pueden leer) y no pasan por los filtros anteriores, así que pueden llevar cualquier símbolo.

Los filtros de seguridad son **middleware**: código que revisa cada petición antes de que llegue al controller. Están en `app/Http/Middleware/` (`DetectarInyeccionSql`, `PrevenirXss` y `CabecerasSeguridad`) y se activan para toda la API en `app/Http/Kernel.php`, así que ningún módulo tuvo que cambiar su código.

## Códigos de respuesta

| Código | Significa |
|---|---|
| **200** | Todo bien. |
| **201** | Se creó el registro. |
| **401** | Falta el token o no es válido. |
| **403** | Tienes token, pero no tienes permiso (no eres admin). |
| **404** | El registro no existe. |
| **422** | Los datos no son válidos o rompen una regla (por ejemplo, no hay stock). |
| **429** | Demasiadas peticiones en un minuto. |

## Endpoints

Todas las rutas empiezan con `/api`.

**Públicos**

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/register` | Crea un usuario cliente y devuelve un token. |
| POST | `/login` | Inicia sesión y devuelve un token. |
| GET | `/marcas` | Lista de marcas. Búsqueda por nombre con `?q=`. |
| GET | `/marcas/{id}` | Detalle de una marca. |
| GET | `/modelos` | Lista de modelos. Filtros: `marca_id`, `tipo`, `anio`, `precio_min`, `precio_max` y `q` (nombre). |
| GET | `/modelos/{id}` | Detalle de un modelo con su marca y las sucursales donde está. |
| GET | `/sucursales` | Lista de sucursales. |
| GET | `/sucursales/{id}` | Detalle de una sucursal. |
| GET | `/sucursales/{id}/inventario` | Modelos y stock de una sucursal. |

**Con token**

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/user` | Datos del usuario del token. |
| POST | `/logout` | Cierra la sesión (el token deja de servir). |
| POST | `/pedidos` | Crea un pedido y descuenta una unidad del stock. |

**Solo admin**

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/pedidos` | Lista de pedidos, del más reciente al más antiguo (15 por página, `?page=2`). |
| PATCH | `/pedidos/{id}/estado` | Confirma o cancela un pedido pendiente. |
| GET | `/ventas` | Lista de ventas (pedidos confirmados). |
| POST / PUT / DELETE | `/marcas`, `/marcas/{id}` | Crear, editar y borrar marcas. |
| POST / PUT / DELETE | `/modelos`, `/modelos/{id}` | Crear, editar y borrar modelos. |
| POST / PUT / DELETE | `/sucursales`, `/sucursales/{id}` | Crear, editar y borrar sucursales. |
| POST | `/sucursales/{id}/inventario` | Agrega un modelo a una sucursal con su stock. |
| PUT | `/sucursales/{id}/inventario/{modeloId}` | Cambia el stock de un modelo en una sucursal. |
| DELETE | `/sucursales/{id}/inventario/{modeloId}` | Quita un modelo de una sucursal. |

## Instalación

### Con Docker (Dev Container, recomendado)

Necesitas Docker y VS Code con la extensión **Dev Containers**.

1. Clona el repositorio, ábrelo en VS Code y elige **"Reopen in Container"**. Se levantan dos servicios:
   - `app`: PHP 8.1
   - `db`: MySQL 8.0 con la base `larosite`

   Al crearse por primera vez, el contenedor instala las dependencias, crea el archivo `.env` con los datos de la base y genera la clave de la aplicación.
2. En el `.env`, escribe una contraseña en `ADMIN_PASSWORD` (será la del usuario admin).
3. Crea las tablas y carga los datos de prueba:
   ```
   php artisan migrate --seed
   ```
4. Inicia el servidor:
   ```
   php artisan serve --host=0.0.0.0
   ```
5. Abre `http://localhost:8000/api/marcas`. Si ves una lista de marcas en JSON, todo funciona.

Si el paso 1 no creó el `.env`, hazlo a mano: `composer install`, copia `.env.example` a `.env` (con `DB_HOST=db` y `DB_DATABASE=larosite`) y corre `php artisan key:generate`.

### Sin Docker

Necesitas PHP 8 con la extensión `pdo_mysql`, Composer y MySQL 8. Crea una base llamada `larosite`, copia `.env.example` a `.env` con tus datos de MySQL y sigue los mismos pasos: `composer install`, `php artisan key:generate`, `php artisan migrate --seed` y `php artisan serve`.

## Probar la API con Postman

1. **Regístrate:** `POST http://localhost:8000/api/register` con este body (raw, JSON):
   ```json
   { "name": "Juan Pérez", "email": "juan@test.com", "password": "secreto123" }
   ```
   La respuesta trae un `token`. Cópialo.

2. **Crea un pedido:** `POST http://localhost:8000/api/pedidos`. En la pestaña *Authorization* elige **Bearer Token** y pega el token. Body:
   ```json
   { "modelo_id": 1, "sucursal_id": 1, "cliente_nombre": "Juan Pérez", "cliente_email": "juan@test.com" }
   ```
   Responde **201** (los valores dependen de los datos de prueba):
   ```json
   {
       "modelo_id": 1,
       "sucursal_id": 1,
       "cliente_nombre": "Juan Pérez",
       "total": 330000,
       "estado": "pendiente",
       "created_at": "2026-11-13T05:11:09.000000Z",
       "id": 1
   }
   ```

3. **Prueba la validación:** envía el mismo pedido sin `sucursal_id` y con un email mal escrito. Responde **422** con un mensaje por cada campo:
   ```json
   {
       "message": "The given data was invalid.",
       "errors": {
           "sucursal_id": ["The sucursal id field is required."],
           "cliente_email": ["The cliente email must be a valid email address."]
       }
   }
   ```

Para confirmar o cancelar pedidos, inicia sesión con el usuario admin (`ADMIN_EMAIL` y `ADMIN_PASSWORD` de tu `.env`) y usa ese token.

## Pruebas automáticas

```
php artisan test
```

Hay 36 pruebas que revisan los pedidos, las ventas, las marcas y la seguridad. Usan una base de datos temporal en memoria (SQLite), así que no tocan los datos de `larosite`.

## Estructura de carpetas

```
app/
├── Http/
│   ├── Controllers/   reciben la petición y devuelven la respuesta
│   ├── Requests/      reglas de validación de cada formulario
│   └── Middleware/    filtros de seguridad
├── Services/          reglas del negocio (stock, estados de pedidos, búsquedas)
└── Models/            una clase por tabla, con sus relaciones
database/
├── migrations/        cómo se crea cada tabla
└── seeders/           datos de prueba y usuario admin
routes/api.php         todas las rutas de la API
tests/Feature/         pruebas automáticas
```

## Equipo

- Carlos Ambriz
- Aaron Arias
- Jesus Cuevas

**CESUN Universidad — Backend II**

## Licencia

Este proyecto está bajo la Licencia MIT.
