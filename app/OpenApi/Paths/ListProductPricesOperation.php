<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/products/{id}/prices',
    description: 'Devuelve el producto con el atributo price_list: el precio base (id 0, is_base_price true) y las filas de product_prices (is_base_price false, con currency).',
    summary: 'Listar precios del producto',
    tags: ['Products'],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer'),
            example: 1,
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Producto con su lista de precios.',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/ProductWithPrices'),
                ],
                type: 'object',
            ),
        ),
        new OA\Response(
            response: 404,
            description: 'Producto inexistente.',
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
class ListProductPricesOperation {}
