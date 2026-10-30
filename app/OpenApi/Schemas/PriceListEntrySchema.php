<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PriceListEntry',
    description: 'Elemento de price_list construido en ProductRepository::getByPricesPerProduct. La fila base usa id 0 y no incluye currency.',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 0),
        new OA\Property(property: 'product_id', type: 'integer', example: 1),
        new OA\Property(property: 'currency_id', type: 'integer', example: 1),
        new OA\Property(
            property: 'price',
            description: 'En price_list el precio se castea a float, a diferencia del recurso Product.',
            type: 'number',
            format: 'float',
            example: 99.99,
        ),
        new OA\Property(
            property: 'currency',
            description: 'Solo presente en las filas que no son precio base.',
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Euro'),
                new OA\Property(property: 'symbol', type: 'string', example: '€'),
            ],
            type: 'object',
        ),
        new OA\Property(property: 'is_base_price', type: 'boolean', example: false),
    ],
)]
class PriceListEntrySchema {}
