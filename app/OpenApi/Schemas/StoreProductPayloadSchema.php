<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreProductPayload',
    description: 'Body de POST /products, según StoreProductRequest.',
    type: 'object',
    required: ['name', 'description', 'price', 'currency_id', 'tax_cost', 'manufacturing_cost'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Wireless Keyboard'),
        new OA\Property(property: 'description', type: 'string', example: 'Teclado inalámbrico con retroiluminación.'),
        new OA\Property(
            property: 'price',
            description: 'numeric|min:0|max:99999999.9999|decimal:0,4',
            type: 'number',
            format: 'float',
            example: 99.99,
        ),
        new OA\Property(property: 'currency_id', description: 'exists:currencies,id', type: 'integer', example: 1),
        new OA\Property(
            property: 'tax_cost',
            description: 'numeric|min:0|max:99999999.9999|decimal:0,4',
            type: 'number',
            format: 'float',
            example: 12.5,
        ),
        new OA\Property(
            property: 'manufacturing_cost',
            description: 'numeric|min:0|max:99999999.9999|decimal:0,4',
            type: 'number',
            format: 'float',
            example: 45,
        ),
        new OA\Property(
            property: 'prices',
            description: 'Opcional, máximo 50 elementos; currency_id debe ser distinct dentro del lote.',
            type: 'array',
            maxItems: 50,
            items: new OA\Items(
                type: 'object',
                required: ['currency_id', 'price'],
                properties: [
                    new OA\Property(property: 'currency_id', type: 'integer', example: 2),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 89.5),
                ],
            ),
        ),
    ],
)]
class StoreProductPayloadSchema {}
