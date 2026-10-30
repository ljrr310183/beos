<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateProductPayload',
    description: 'Body de PUT /products/{id}, según UpdateProductRequest. A diferencia de POST, description es present|nullable.',
    type: 'object',
    required: ['name', 'description', 'price', 'currency_id', 'tax_cost', 'manufacturing_cost'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Wireless Keyboard Pro'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Teclado inalámbrico con retroiluminación.'),
        new OA\Property(
            property: 'price',
            description: 'numeric|min:0|max:99999999.9999|decimal:0,4',
            type: 'number',
            format: 'float',
            example: 109.99,
        ),
        new OA\Property(property: 'currency_id', description: 'exists:currencies,id', type: 'integer', example: 1),
        new OA\Property(
            property: 'tax_cost',
            description: 'numeric|min:0|max:99999999.9999|decimal:0,4',
            type: 'number',
            format: 'float',
            example: 13.75,
        ),
        new OA\Property(
            property: 'manufacturing_cost',
            description: 'numeric|min:0|max:99999999.9999|decimal:0,4',
            type: 'number',
            format: 'float',
            example: 47.5,
        ),
        new OA\Property(
            property: 'prices',
            description: 'Si se envía, reemplaza el set completo de precios. Si se omite, los precios actuales quedan intactos.',
            type: 'array',
            maxItems: 50,
            items: new OA\Items(
                type: 'object',
                required: ['currency_id', 'price'],
                properties: [
                    new OA\Property(property: 'currency_id', type: 'integer', example: 2),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 98.4),
                ],
            ),
        ),
    ],
)]
class UpdateProductPayloadSchema {}
