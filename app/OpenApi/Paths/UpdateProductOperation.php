<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Put(
    path: '/products/{id}',
    description: 'Actualiza el producto (PUT, no PATCH: la ruta solo acepta PUT y PATCH responde 405). Si el body incluye prices, el set de precios se reemplaza completo; si se omite, los precios actuales no se tocan.',
    summary: 'Actualizar producto',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdateProductPayload'),
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
            response: 200,
            description: 'Producto actualizado.',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
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
            response: 405,
            description: 'Método no permitido para la ruta (por ejemplo PATCH).',
            content: new OA\JsonContent(
                example: ['message' => 'El método HTTP no está soportado para esta ruta.', 'error' => 'METHOD_NOT_ALLOWED'],
                ref: '#/components/schemas/Error',
            ),
        ),
        new OA\Response(
            response: 422,
            description: 'Validación fallida.',
            content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
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
class UpdateProductOperation {}
