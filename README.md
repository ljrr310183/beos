# Catálogo de Productos y Precios Multidivisa

API REST en Laravel para administrar un catálogo de productos. Cada producto tiene un precio base en su divisa principal y puede tener precios alternativos en otras divisas.

El contrato de esta API está descrito en tres soportes, todos en español y generados a partir del código:

| Soporte | Ubicación | Contenido |
|---|---|---|
| Código | `routes/api.php`, `app/Http/Requests/*` | Fuente de verdad de rutas y validación |
| Swagger UI | `http://localhost:8000/api/documentation` | Documentación interactiva OpenAPI 3.0 (7 operaciones, 11 schemas) |
| Colección Postman | `postman/begento-productos.postman_collection.json` | 11 peticiones listas para ejecutar |

Los detalles de cada URL están en [Documentación Swagger](#documentación-swagger) y [Colección Postman](#colección-postman).

## Stack

| Componente | Versión |
|---|---|
| PHP | `^8.3` (probado con 8.4.5) |
| Laravel | `^13.17` |
| Pest | `^5.3` |
| Laravel Pint | `^1.27` |
| l5-swagger (zircote/swagger-php 7) | `^11.2` |
| Base de datos | SQLite por defecto (`DB_CONNECTION=sqlite` en `.env.example`); MySQL soportado |
| Frontend build | Vite + Tailwind 4 (solo para `resources/`, no necesario para la API) |

## Instalación

Requisitos: PHP 8.3+, Composer. Node solo si vas a compilar assets.

```bash
git clone https://github.com/kamuxx/beos.git
cd beos
composer run setup
```

El repositorio es `https://github.com/kamuxx/beos.git` (rama `main`).

`composer run setup` ejecuta `composer install`, crea `.env` desde `.env.example`, genera la `APP_KEY`, corre `php artisan migrate --force`, instala npm y compila assets. **No siembra datos**: después de `setup` ejecuta `php artisan db:seed` si quieres el catálogo de ejemplo.

Paso manual equivalente:

```bash
git clone https://github.com/kamuxx/beos.git
cd beos
composer install
copy .env.example .env
php artisan key:generate
type nul > database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Si usas MySQL, ajusta `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` en `.env`, omite la creación del archivo SQLite y ejecuta `php artisan migrate --seed`.

## Base de datos

Tres migraciones, en este orden:

| Tabla | Puntos relevantes |
|---|---|
| `currencies` | `name` varchar(100), `symbol` varchar(10), `exchange_rate` decimal(12,4) |
| `products` | `name` varchar(255) único (`products_name_unique`), `description` text nullable, `price` / `tax_cost` / `manufacturing_cost` decimal(12,4), `currency_id` FK con `restrictOnDelete` |
| `product_prices` | `product_id` FK con `cascadeOnDelete`, `currency_id` FK con `restrictOnDelete`, `price` decimal(12,4), índice único `uk_product_currency` sobre `(product_id, currency_id)` |

`DatabaseSeeder` llama a `CurrencySeeder`, `ProductSeeder` y `ProductPriceSeeder`. El catálogo de divisas que crea `CurrencyFactory` es determinista: `usd` (1), `eur` (2), `ves` (3), `ars` (4), `chf` (5).

## Endpoints

Todas las rutas llevan el prefijo `/api`, que aplica `bootstrap/app.php` (`withRouting(api: ...)`); en `routes/api.php` solo se declara el prefijo `products`. Los endpoints con `{id}` usan `whereNumber('id')`, así que un id no numérico no hace match y responde 404.

| Método | URI | Nombre | Acción | Éxito |
|---|---|---|---|---|
| GET | `/api/products` | `products.index` | `ProductController@index` | 200 |
| POST | `/api/products` | `products.create` | `ProductController@store` | 201 |
| GET | `/api/products/{id}` | `products.show` | `ProductController@show` | 200 |
| PUT | `/api/products/{id}` | `products.update` | `ProductController@update` | 200 |
| DELETE | `/api/products/{id}` | `products.destroy` | `ProductController@destroy` | 200 |
| GET | `/api/products/{id}/prices` | `products.prices` | `ProductController@prices` | 200 |
| POST | `/api/products/{id}/prices` | `products.storePrices` | `ProductController@storePrices` | 201 |

No hay `PATCH`: la actualización es `PUT` y un `PATCH` sobre `/api/products/{id}` responde 405.

`DELETE` responde 200 con `{"message":"Producto eliminado correctamente."}` (no 204). `GET /api/products/{id}/prices` no es un listado aparte: devuelve el producto con el atributo `price_list`.

## Formato de las respuestas

### Producto (`GET /api/products/{id}`, `POST`, `PUT`)

```json
{
  "data": {
    "id": 1,
    "name": "Wireless Keyboard",
    "description": "Teclado inalámbrico con retroiluminación.",
    "price": "99.9900",
    "tax_cost": "12.5000",
    "manufacturing_cost": "45.0000",
    "currency": { "id": 1, "name": "US Dollar", "symbol": "$", "exchange_rate": "1.0000" },
    "created_at": "2026-10-29T16:20:00.000000Z",
    "updated_at": "2026-10-29T16:20:00.000000Z"
  }
}
```

`price`, `tax_cost`, `manufacturing_cost` y `exchange_rate` viajan como **string** porque los modelos los castean con `decimal:4`.

### Producto con precios (`GET /api/products/{id}/prices`)

```json
{
  "data": {
    "id": 1,
    "name": "Wireless Keyboard",
    "description": "Teclado inalámbrico con retroiluminación.",
    "price": "99.9900",
    "tax_cost": "12.5000",
    "manufacturing_cost": "45.0000",
    "price_list": [
      { "id": 0, "product_id": 1, "currency_id": 1, "price": 99.99, "is_base_price": true },
      {
        "id": 7,
        "product_id": 1,
        "currency_id": 2,
        "price": 89.5,
        "currency": { "name": "Euro", "symbol": "€" },
        "is_base_price": false
      }
    ]
  }
}
```

Cuando `ProductResource` recibe `price_list`, **omite** `currency`, `created_at` y `updated_at`. En `price_list` el precio es `number` (el repositorio castea a float) y la fila base usa `id: 0`.

### Listado paginado (`GET /api/products`)

Envelope estándar del paginador de Laravel, con `data[]`, `links{first,last,prev,next}` y `meta{current_page, from, last_page, links[], path, per_page, to, total}`.

- `page` menor que 1 se normaliza a 1.
- `per_page` fuera del rango 1..100 se reemplaza por 15 (valor por defecto).

### Precios registrados (`POST /api/products/{id}/prices`)

```json
{
  "data": [
    {
      "id": 5,
      "product_id": 1,
      "price": "280.5000",
      "currency": { "id": 5, "name": "Swiss Franc", "symbol": "CHF", "exchange_rate": "0.8290" },
      "created_at": "2026-10-29T16:30:00.000000Z",
      "updated_at": "2026-10-29T16:30:00.000000Z"
    }
  ]
}
```

## Envelope de errores

Definido en `bootstrap/app.php`. Siempre `{message, error}`; el 422 añade `errors`.

| Status | `error` | `message` |
|---|---|---|
| 404 | `NOT_FOUND` | `Producto no encontrado.` |
| 405 | `METHOD_NOT_ALLOWED` | `El método HTTP no está soportado para esta ruta.` |
| 429 | `TOO_MANY_REQUESTS` | `Demasiadas solicitudes. Inténtalo de nuevo en unos segundos.` |
| 422 | `VALIDATION_ERROR` | `Los datos proporcionados no son válidos.` |
| 500 | `SERVER_ERROR` | `Error interno del servidor.` (solo con `APP_DEBUG=false`) |

## Validación

### `POST /api/products` — `StoreProductRequest`

Obligatorios: `name` (string, máx. 255, único en `products`), `description` (string), `price`, `tax_cost`, `manufacturing_cost` (`numeric`, `min:0`, `max:99999999.9999`, `decimal:0,4`) y `currency_id` (`exists:currencies,id`).

`prices` es opcional: array de máximo 50 elementos, cada uno con `currency_id` (`exists` y `distinct` dentro del lote) y `price` (`numeric`, `min:0`, `max:9999999999.9999`, `decimal:0,4`). El producto y sus precios se crean en una sola transacción.

### `PUT /api/products/{id}` — `UpdateProductRequest`

Mismas reglas que `POST` con dos diferencias:

- `name` ignora el propio producto en la regla `unique`.
- `description` es `present|nullable|string`.

Si el body incluye `prices`, el set de precios del producto se reemplaza completo. Si se omite, los precios actuales no se tocan.

### `POST /api/products/{id}/prices` — `StorePriceRequest`

El body es un **arreglo JSON de precios en la raíz**, sin objeto que lo envuelva:

```json
[
  { "product_id": 1, "currency_id": 2, "price": 280.5 },
  { "product_id": 1, "currency_id": 5, "price": 310.75 }
]
```

Reglas:

- Las claves permitidas salen de `ProductPrice::$fillable`: `product_id`, `currency_id`, `price`.
- `product_id` es obligatorio y debe coincidir con el `{id}` de la ruta (`Rule::in`).
- `currency_id` debe existir, ser `distinct` dentro del lote y no estar ya registrado para ese producto.
- Un payload que no sea lista devuelve `payload` → `El payload debe ser un arreglo de precios.`
- Una lista vacía devuelve `payload` → `El listado de precios debe contener al menos un elemento.`
- Una lista con más de 50 elementos devuelve `payload` → `El listado de precios no puede superar los 50 elementos.` (mismo tope que el array `prices` de `StoreProductRequest`)
- Un atributo fuera de `$fillable` devuelve `{index}.payload` → `El payload contiene atributos no soportados: ...`
- Si la clave única `uk_product_currency` rechaza la inserción por concurrencia, el `QueryException` 23000 se convierte en 422 con `payload` → `El precio para esta divisa ya ha sido registrado para este producto.`

## Documentación Swagger

La documentación de la API es **Swagger (OpenAPI 3.0) y está redactada íntegramente en español**: títulos, descripciones, parámetros, ejemplos y mensajes de error. Las anotaciones viven en `app/OpenApi/` — `OpenApiSpec` para la información global, `Schemas/` para los modelos y `Paths/` para las 7 operaciones. La configuración está en `config/l5-swagger.php`.

```bash
php artisan l5-swagger:generate
```

| Recurso | URL | Contenido |
|---|---|---|
| Swagger UI | `http://localhost:8000/api/documentation` | Interfaz interactiva para probar los 7 endpoints |
| JSON OpenAPI | `http://localhost:8000/docs` | Especificación cruda (`api-docs.json`) |
| Archivo en disco | `storage/api-docs/api-docs.json` | Artefacto generado por el comando |

La ruta de la UI es `api/documentation` (config `routes.api`) y la del JSON es `docs` (config `routes.docs`), fuera del prefijo `/api`. El middleware de ambas está vacío en la config, así que la documentación es pública en local. Regenera el JSON cada vez que cambies una ruta, un FormRequest o un Resource.

## Colección Postman

La colección está versionada en el repo en **[`postman/begento-productos.postman_collection.json`](postman/begento-productos.postman_collection.json)** (formato Postman Collection v2.1). No hay que descargar nada externo: viene con el `git clone`.

Para usarla: Postman → *Import* → *File* → selecciona ese JSON. Variables:

| Variable | Valor por defecto | Uso |
|---|---|---|
| `baseUrl` | `http://localhost:8000/api` | Prefijo de todas las peticiones |
| `productId` | `1` | Id de un producto existente en tu BD |

La colección incluye las 7 peticiones y cuatro casos de error ya documentados (404, 405 por `PATCH`, 422 de producto y 422 de payload no-lista). El `POST /api/products` usa `{{$timestamp}}` en el nombre para no chocar con la regla `unique`.

La misma suite está replicada para la extensión **REST Client** de VS Code en [`postman/begento-productos.http`](postman/begento-productos.http): 11 bloques con las mismas peticiones, cabeceras y cuerpos. Se abre el archivo y se pulsa *Send Request* sobre cada bloque `###`, sin importar nada.

## Decisiones de seguridad

| Control | Dónde | Comportamiento |
|---|---|---|
| Rate limiting | `AppServiceProvider::boot()` + `bootstrap/app.php` (`throttleApi()`) | Limiter nombrado `api`: 60 peticiones por minuto **por IP** sobre todo el grupo `api`. Al agotarse devuelve 429 con `Retry-After` y `X-RateLimit-*`. |
| CORS | `config/cors.php` | Solo `api/*`. Un único origen permitido: `FRONTEND_URL` (por defecto `http://localhost:3000`). `/up` y las rutas web no emiten cabeceras CORS. |
| Proxies | `bootstrap/app.php` (`trustProxies(at: '*')`) | Necesario para que `$request->ip()` sea la IP real del cliente detrás de un balanceador y el rate limiting no agrupe a todos los usuarios bajo la IP del proxy. |
| URI malformada | `App\Http\Middleware\RejectMalformedUri` (global) | Rechaza URIs no válidas antes del router. |
| Asignación masiva | `$fillable` en los tres modelos | Solo se persisten los atributos declarados; el resto se descarta. |
| Fuga de traza | `bootstrap/app.php` | Con `APP_DEBUG=false` el 500 devuelve solo `SERVER_ERROR`, sin stack ni SQL. |
| Carrera en `unique` | `StoreProductUseCase` | Un `QueryException` con código `23000` se traduce a 422 `VALIDATION_ERROR` en `name`; cualquier otro código se re-lanza. |

**Fuera de alcance:** el enunciado del desafío no pide autenticación, así que los 7 endpoints son públicos y no se añadió Sanctum ni tokens. El rate limiting por IP es la única barrera frente a abuso.

## Pruebas y estilo

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

La suite cubre los 7 endpoints, el envelope de errores, la paginación, las reglas de validación, el rate limiting y CORS: 71 tests / 450 aserciones.

### Reporte de cobertura

```bash
XDEBUG_MODE=coverage php artisan test --coverage
XDEBUG_MODE=coverage vendor/bin/pest --coverage-html=build/coverage
```

`XDEBUG_MODE=coverage` es necesario porque `php.ini` trae `xdebug.mode=debug`. El alcance medido es el que declara `phpunit.xml` en `<source><include><directory>app</directory>`, así que `routes/`, `config/` y `database/` no entran en el porcentaje.

Resultado de la última corrida: 71 tests y 450 aserciones en verde.

| Alcance | Archivos | Líneas cubiertas | Líneas ejecutables | Cobertura |
|---|---:|---:|---:|---:|
| `app/` completo | 41 | 377 | 381 | 99.0 % |
| Capas del desafío: controllers, middleware, form requests, resources, repositorios, casos de uso, modelos de dominio, OpenAPI y providers | 40 | 377 | 377 | 100.0 % |
| `Models/User.php` (scaffolding de autenticación, sin uso en esta API) | 1 | 0 | 4 | 0.0 % |

En 100 %: `ProductController`, `RejectMalformedUri`, los tres FormRequests (`StoreProductRequest`, `UpdateProductRequest`, `StorePriceRequest`), `ProductRepository`, los siete casos de uso, los tres modelos de dominio (`Product`, `ProductPrice`, `Currency`), los tres Resources, `AppServiceProvider`, las siete operaciones de `app/OpenApi/Paths` y los once schemas de `app/OpenApi/Schemas`.

El único archivo sin cubrir es `Models/User.php`, el stub de autenticación que trae Laravel: la prueba no define autenticación, así que se deja fuera del alcance declarado en lugar de testearlo por conveniencia. Por eso el total sobre `app/` queda en 99.0 % y no en 100 %.

El `--coverage-html` deja el detalle por línea en `build/coverage/index.html`; ese directorio está en `.gitignore` porque es un artefacto local.

### Cobertura frente al enunciado

`instrucciones/Prueba Backend.md` pide 7 endpoints, 3 tablas de datos y 4 notas técnicas, y define 4 criterios de evaluación y 4 formatos de entrega. Esta tabla mapea cada ítem contra el código real y las pruebas que lo ejercitan, con la cobertura de sentencias de las clases implicadas en la última corrida.

| Bloque | Ítem del enunciado | Dónde se cumple | Pruebas | Cobertura |
|---|---|---|---|---|
| Endpoint | `GET /api/products` | `ProductController::index:34` → `ListProductsUseCase` → `ProductRepository::getAll:18` | `ListProductsTest` (5) | 100 % |
| Endpoint | `POST /api/products` | `ProductController::store:39` → `StoreProductUseCase` → `ProductRepository::createWithPrices:83` | `StoreProductTest` (14) + `StoreProductNameRaceTest` (2) | 100 % |
| Endpoint | `GET /api/products/{id}` | `ProductController::show:46` → `GetProductUseCase` → `ProductRepository::getById:31` | `GetProductTest` (4) | 100 % |
| Endpoint | `PUT /api/products/{id}` | `ProductController::update:54` → `UpdateProductUseCase` → `ProductRepository::update:100` | `UpdateProductTest` (10) | 100 % |
| Endpoint | `DELETE /api/products/{id}` | `ProductController::destroy:64` → `DeleteProductUseCase` → `Product::delete()` | `DeleteProductTest` (2) | 100 % |
| Endpoint | `GET /api/products/{id}/prices` | `ProductController::prices:74` → `GetPricesPerProductUseCase` → `ProductRepository::getByPricesPerProduct:36` | `GetProductPricesTest` (4) | 100 % |
| Endpoint | `POST /api/products/{id}/prices` | `ProductController::storePrices:82` → `StorePricesUseCase` → `ProductRepository::createPrices:124` | `StoreProductPriceTest` (18) + `StorePricesUseCaseTest` (1) | 100 % |
| Datos | `products`: `name`, `description`, `price`, `currency_id`, `tax_cost`, `manufacturing_cost` | `create_products_table` + `Product::$fillable` | `StoreProductTest`, `UpdateProductTest` | 100 % |
| Datos | `currencies`: `name`, `symbol`, `exchange_rate` | `create_currencies_table` + `Currency` | `StoreProductTest`, `ModelRelationsTest` | 100 % |
| Datos | `product_prices`: `product_id`, `currency_id`, `price` | `create_product_prices_table` + `ProductPrice` | `StoreProductPriceTest`, `GetProductPricesTest` | 100 % |
| Nota | Laravel 8 o superior | `laravel/framework 13.39.0` sobre PHP 8.4.5 | — | — |
| Nota | Uso de Eloquent para la base de datos | `Product`/`ProductPrice`/`Currency` con `hasMany` y `belongsTo`; ninguna query con `DB::raw` | `ModelRelationsTest` (1) | 100 % |
| Nota | Respuestas en formato JSON | `ProductResource`, `ProductPriceResource`, `CurrencyResource` + envelope único de `bootstrap/app.php` | `ErrorEnvelopeTest` (5) | 100 % |
| Nota | Documentación clara y concisa | Swagger en `/api/documentation`, colección Postman y este README | — | — |
| Evaluación | Correctitud | Los 7 endpoints responden con el contrato declarado, incluidos 404, 405, 422 y 429 | 71 pruebas, todas en verde | 99.0 % |
| Evaluación | Claridad | El mismo contrato en tres formatos verificables: Swagger publicado, colección Postman ejecutable y README con request y response reales | 7 endpoints en los tres artefactos | — |
| Evaluación | Organización | Flujo `ruta → controlador → caso de uso → repositorio`, una responsabilidad por clase y `whereNumber` en todas las rutas con `{id}` | Las 20 clases de `app/` con sentencias ejecutables, al 100 % | 100 % |
| Evaluación | Seguridad | `throttleApi()` con 60 req/min por IP, `RejectMalformedUri` antes del router, CORS de un solo origen y envelope de error que no filtra trazas | `RateLimitTest` (2) + `CorsTest` (3) + `ErrorEnvelopeTest` (5) | 100 % |
| Entrega | Documentación Swagger | `app/OpenApi/` generado a `/api/documentation` | 7 operaciones + 11 schemas | 100 % |
| Entrega | Colección Postman | `postman/begento-productos.postman_collection.json`: 11 requests en 3 carpetas (Productos, Precios, Errores documentados) | — | — |
| Entrega | Colección Insomnia | No se entrega: el enunciado pide **una** de las tecnologías y Swagger + Postman cubren el mismo contrato | — | — |
| Entrega | Documento | Este `README.md` | — | — |

Las 40 clases que implementan el enunciado tienen 377/377 sentencias cubiertas. La única clase sin cobertura en `app/` es `Models/User.php`, el stub de autenticación que no forma parte del desafío, y es la que deja el total en 99.0 %.

### Salida de la suite

Captura de la última corrida completa (71 tests, 450 aserciones, sin fallos). Los tiempos por prueba salen del log JUnit de esa misma corrida (`php artisan test --log-junit=build/junit.xml`):

```text
  PASS  Tests\Feature\CorsTest
  ✓ it answers the preflight of the configured frontend origin  0.56s
  ✓ it never echoes an origin that is not the configured one  0.27s
  ✓ it does not expose CORS headers on non api paths  0.28s

  PASS  Tests\Feature\DeleteProductTest
  ✓ it deletes the product and cascades its prices  1.13s
  ✓ it returns 404 when deleting a product that does not exist  0.31s

  PASS  Tests\Feature\ErrorEnvelopeTest
  ✓ it returns the standard envelope when the http method is not allowed  0.34s
  ✓ it always includes a non empty errors object on validation failures  0.34s
  ✓ it hides the trace of an unhandled exception in production  0.32s
  ✓ it ignores unknown query parameters  0.8s
  ✓ it rejects an api uri that carries a fragment delimiter  0.29s

  PASS  Tests\Feature\GetProductPricesTest
  ✓ it returns the base price followed by the alternate prices of the product  0.45s
  ✓ it returns only the base price when the product has no alternate prices  0.4s
  ✓ it does not return prices belonging to other products  0.49s
  ✓ it returns 404 when the product does not exist  0.39s

  PASS  Tests\Feature\GetProductTest
  ✓ it returns a product with its nested currency  0.45s
  ✓ it returns 404 when the product does not exist  0.37s
  ✓ it returns 404 when the route id is not numeric  0.37s
  ✓ it keeps the json envelope when the accept header is missing  0.35s

  PASS  Tests\Feature\ListProductsTest
  ✓ it returns the paginated list of products  1.19s
  ✓ it returns an empty paginated list when there are no products  0.42s
  ✓ it normalises a page below one to the first page  0.88s
  ✓ it falls back to the default page size when per_page is out of range  1.14s
  ✓ it returns an empty data set when the page is beyond the last one  0.42s

  PASS  Tests\Feature\ModelRelationsTest
  ✓ it exposes the inverse relations of a currency and a product price  0.36s

  PASS  Tests\Feature\RateLimitTest
  ✓ it throttles the api group once the per minute allowance is exhausted  3.9s
  ✓ it applies the same allowance to every route of the api group  3.84s

  PASS  Tests\Feature\StorePricesUseCaseTest
  ✓ it rethrows a database failure that is not a duplicate key  0.51s

  PASS  Tests\Feature\StoreProductNameRaceTest
  ✓ it reports a duplicate product name raised by the database as a validation error  0.46s
  ✓ it rethrows a database failure that is not a duplicate key  0.52s

  PASS  Tests\Feature\StoreProductPriceTest
  ✓ it registers a list of alternate currency prices  0.5s
  ✓ it rejects a duplicated currency for the same product without modifying the stored row  0.41s
  ✓ it rejects duplicated currencies inside the same payload  0.56s
  ✓ it rolls back the whole batch when one element fails  0.43s
  ✓ it returns 404 when registering prices on a product that does not exist  0.37s
  ✓ it rejects a product_id that does not match the route  0.45s
  ✓ it rejects an unknown currency  0.4s
  ✓ it accepts an explicit price in the product base currency  0.44s
  ✓ it rejects a price with more than four decimal places  0.41s
  ✓ it rejects an empty price list  0.36s
  ✓ it rejects a payload that is not a list  0.38s
  ✓ it rejects a single price object outside the list  0.4s
  ✓ it rejects attributes outside the product price fillable set  0.4s
  ✓ it registers a batch with a single price  0.47s
  ✓ it rejects a list that mixes price objects with a scalar element  0.44s
  ✓ it accepts the maximum batch of 50 prices  2.96s
  ✓ it rejects a batch larger than 50 prices  2.11s
  ✓ it reports a duplicate key raised by the database as a payload validation error  0.44s

  PASS  Tests\Feature\StoreProductTest
  ✓ it creates a product without prices  0.41s
  ✓ it creates a product with prices in a single transaction  0.49s
  ✓ it rejects a duplicate product name  0.48s
  ✓ it rejects an unknown price currency and persists nothing  0.4s
  ✓ it rejects duplicate currencies inside prices  0.41s
  ✓ it rejects non array prices entries  0.37s
  ✓ it rejects invalid required fields reporting one error key per field  0.36s
  ✓ it rejects a base currency that does not exist  0.37s
  ✓ it rejects a price with more than four decimal places  0.37s
  ✓ it rejects a price above the column maximum  0.36s
  ✓ it reports the spanish duplicate name message and leaves the existing product untouched  0.49s
  ✓ it trims the name before checking uniqueness  0.49s
  ✓ it persists a numeric string price normalised to four decimals  0.4s
  ✓ it ignores undeclared fields in the payload  0.39s

  PASS  Tests\Feature\UpdateProductTest
  ✓ it replaces every field of an existing product  0.48s
  ✓ it accepts a null description  0.48s
  ✓ it returns 404 when the product does not exist  0.37s
  ✓ it rejects an invalid price and an unknown currency without touching the row  0.42s
  ✓ it rejects a payload missing a required field without touching the row  0.4s
  ✓ it rejects a payload with the description key omitted  0.4s
  ✓ it does not support patch  0.36s
  ✓ it accepts its own name but rejects the name of another product  0.59s
  ✓ it replaces the whole price list when prices are sent  0.56s
  ✓ it leaves the price list untouched when prices are not sent  0.5s

  Tests:    71 passed (450 assertions)
  Duration: 44.76s
```

## Estructura

```
app/
├── Http/
│   ├── Controllers/ProductController.php   # 7 acciones, una por endpoint
│   ├── Requests/                            # StoreProductRequest, UpdateProductRequest, StorePriceRequest
│   └── Resources/                           # ProductResource, ProductPriceResource, CurrencyResource
├── Models/                                  # Product, ProductPrice, Currency
├── OpenApi/                                 # Anotaciones Swagger (Info, Schemas, Paths)
├── Repositories/ProductRepository.php       # Paginación, price_list y escrituras transaccionales
└── UseCases/Product/                        # Un caso de uso por acción del controlador
```

El controlador solo orquesta: valida vía FormRequest, delega en un caso de uso y serializa con un Resource. El acceso a datos queda en `ProductRepository`.
