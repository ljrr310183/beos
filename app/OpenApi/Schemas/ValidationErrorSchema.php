<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ValidationError',
    description: 'Error 422: mensaje genérico más el detalle por campo que arma Laravel.',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Los datos proporcionados no son válidos.'),
        new OA\Property(property: 'error', type: 'string', example: 'VALIDATION_ERROR'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
            example: ['name' => ['El nombre del producto ya está registrado.']],
        ),
    ],
)]
class ValidationErrorSchema {}
