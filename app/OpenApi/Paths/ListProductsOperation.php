<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/products',
    description: 'Lista paginada de productos con su divisa. La ruta se declara como /api/products porque el prefijo api lo aplica withRouting(api: ...).',
    summary: 'Listar productos',
    tags: ['Products'],
    parameters: [
        new OA\Parameter(
            name: 'page',
            description: 'Página solicitada. Un valor menor que 1 se normaliza a 1.',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
            example: 1,
        ),
        new OA\Parameter(
            name: 'per_page',
            description: 'Tamaño de página. Fuera del rango 1..100 se usa 15.',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15),
            example: 15,
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Colección paginada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ProductList'),
        ),
        new OA\Response(
            response: 500,
            description: 'Error interno. Solo se devuelve el mensaje genérico cuando APP_DEBUG=false.',
            content: new OA\JsonContent(ref: '#/components/schemas/Error'),
        ),
        new OA\Response(
            response: 429,
            description: 'Límite de peticiones excedido: el grupo api admite 60 peticiones por minuto y por IP.',
            headers: [
                new OA\Header(header: 'Retry-After', description: 'Segundos hasta que la ventana se reinicie.', schema: new OA\Schema(type: 'integer', example: 42)),
                new OA\Header(header: 'X-RateLimit-Limit', description: 'Peticiones permitidas por ventana.', schema: new OA\Schema(type: 'integer', example: 60)),
                new OA\Header(header: 'X-RateLimit-Remaining', description: 'Peticiones restantes en la ventana.', schema: new OA\Schema(type: 'integer', example: 0)),
            ],
            content: new OA\JsonContent(
                example: ['message' => 'Demasiadas solicitudes. Inténtalo de nuevo en unos segundos.', 'error' => 'TOO_MANY_REQUESTS'],
                ref: '#/components/schemas/Error',
            ),
        ),
    ],
)]
class ListProductsOperation {}
