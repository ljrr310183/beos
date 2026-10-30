<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/products/{id}/prices',
    description: 'Registra precios alternativos para el producto. El body es un arreglo JSON de precios sin clave envolvente, con un máximo de 50 elementos; un duplicado de divisa en el lote o contra product_prices responde 422.',
    summary: 'Registrar precios del producto',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            description: 'Arreglo JSON de precios, sin clave envolvente. Máximo 50 elementos.',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/StorePriceItem'),
        ),
    ),
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
            response: 201,
            description: 'Precios registrados.',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/ProductPrice'),
                    ),
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
            response: 422,
            description: 'Validación fallida: payload no es lista, está vacío, incluye atributos fuera de ProductPrice::$fillable o repite una divisa.',
            content: new OA\JsonContent(
                example: [
                    'message' => 'Los datos proporcionados no son válidos.',
                    'error' => 'VALIDATION_ERROR',
                    'errors' => ['payload' => ['El payload debe ser un arreglo de precios.']],
                ],
                ref: '#/components/schemas/ValidationError',
            ),
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
class StoreProductPricesOperation {}
