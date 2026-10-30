<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Error',
    description: 'Envelope de error definido en bootstrap/app.php.',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Producto no encontrado.'),
        new OA\Property(
            property: 'error',
            description: 'Código interno del error.',
            type: 'string',
            enum: ['NOT_FOUND', 'METHOD_NOT_ALLOWED', 'TOO_MANY_REQUESTS', 'SERVER_ERROR'],
            example: 'NOT_FOUND',
        ),
    ],
)]
class ErrorSchema {}
