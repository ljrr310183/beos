<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Delete(
    path: '/products/{id}',
    description: 'Elimina el producto y, por la clave foránea cascadeOnDelete, sus precios. Responde 200 con un mensaje, no 204.',
    summary: 'Eliminar producto',
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
            description: 'Producto eliminado.',
            content: new OA\JsonContent(
                example: ['message' => 'Producto eliminado correctamente.'],
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Producto eliminado correctamente.'),
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
class DeleteProductOperation {}
