<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductWithPrices',
    description: 'ProductResource cuando el modelo trae el atributo price_list: se omiten currency, created_at y updated_at.',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Wireless Keyboard'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Teclado inalámbrico con retroiluminación.'),
        new OA\Property(property: 'price', type: 'string', example: '99.9900'),
        new OA\Property(property: 'tax_cost', type: 'string', example: '12.5000'),
        new OA\Property(property: 'manufacturing_cost', type: 'string', example: '45.0000'),
        new OA\Property(
            property: 'price_list',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PriceListEntry'),
        ),
    ],
)]
class ProductWithPricesSchema {}
