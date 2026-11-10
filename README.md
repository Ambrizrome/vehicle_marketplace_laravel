# Catálogo de Autos API — Backend II

API REST construida con Laravel, desarrollada para la clase de Backend II en CESUN Universidad.

## Descripción del Proyecto

El proyecto es una aplicación backend en Laravel con una API REST para un catálogo de autos: marcas, modelos, sucursales, pedidos y ventas. No tiene páginas web; se prueba con Postman.

## Avance del Trabajo

Hasta el momento hemos realizado lo siguiente:

- **Arquitectura:** flujo Route -> Controller -> Service -> Model; la validación va en FormRequests.
- **Docker:** entorno de desarrollo con Docker Compose (`.devcontainer/docker-compose.yml`) con dos servicios:
  - `app`: PHP 8.1
  - `db`: MySQL 8.0, que crea la base de datos `larosite` e importa automáticamente el respaldo `larosite.sql`.
- **Base de datos:** configuración de la conexión a MySQL y ejecución de las migraciones. Las tablas creadas son:
  - `marcas` — marcas de autos.
  - `modelos` — modelos con año, tipo y precio; cada uno pertenece a una marca.
  - `sucursales` — sucursales (vendedores).
  - `modelo_sucursal` — tabla pivote que relaciona modelos con sucursales y lleva el control del stock.
  - `pedidos` — pedidos de clientes con total y estado (pendiente, confirmado, cancelado).
  - Además de las tablas propias de Laravel (`users`, `password_resets`, `failed_jobs`, `personal_access_tokens`).
- **Modelos:** definición de las relaciones Eloquent: una marca tiene muchos modelos, un modelo pertenece a una marca y está disponible en varias sucursales (relación N a N con stock en la pivote), y un pedido pertenece a un modelo y a una sucursal.
- **API REST:** endpoints implementados en `routes/api.php`:
  - Públicos:
    - `POST /api/register` y `POST /api/login` — devuelven un token para usar como `Authorization: Bearer <token>`.
    - `GET /api/marcas` y `GET /api/marcas/{id}` — lista y detalle de marcas.
    - `GET /api/modelos` y `GET /api/modelos/{id}` — modelos con su marca y las sucursales donde hay stock.
    - `GET /api/sucursales` y `GET /api/sucursales/{id}` — lista y detalle de sucursales.
  - Con token:
    - `GET /api/user` y `POST /api/logout` — usuario del token y cierre de sesión.
    - `POST /api/pedidos` — crea un pedido validando los datos, verificando que la sucursal tenga stock y descontando una unidad del inventario.
  - Con token de admin:
    - `GET /api/pedidos` — lista de pedidos con su modelo, marca y sucursal.
    - `PATCH /api/pedidos/{id}/estado` — confirma o cancela un pedido pendiente; al cancelar, la unidad vuelve al stock.
    - `GET /api/ventas` — lista de ventas (pedidos confirmados).
- **Seeders:** `CatalogoSeeder` para cargar datos de prueba en el catálogo y `AdminSeeder` para crear el usuario admin (necesita `ADMIN_PASSWORD` en el `.env`).
- **Tests:** `php artisan test` (usan SQLite en memoria, no tocan la base `larosite`).

El proyecto sigue en desarrollo y se irán agregando más funcionalidades conforme avance el curso.

## Requisitos

- Docker y Docker Compose (o PHP 7.3+ / 8.x, Composer y MySQL 8.0 para instalación local)

## Instalación

### Con Docker (Dev Container)

1. Clonar el repositorio y abrirlo en VS Code con la extensión Dev Containers.
2. Seleccionar "Reopen in Container"; esto levanta los servicios `app` y `db` automáticamente.
3. Instalar dependencias:
   ```
   composer install
   ```
4. Copiar `.env.example` a `.env` y verificar las credenciales de la base de datos (host: `db`, base: `larosite`).
5. Generar la clave de la aplicación:
   ```
   php artisan key:generate
   ```
6. Ejecutar las migraciones y los seeders:
   ```
   php artisan migrate --seed
   ```
7. Iniciar el servidor:
   ```
   php artisan serve --host=0.0.0.0
   ```

## Equipo

- Carlos Ambriz
- Aaron Arias
- Jesus Cuevas

**CESUN Universidad — Backend II**

## Licencia

Este proyecto está bajo la Licencia MIT.
