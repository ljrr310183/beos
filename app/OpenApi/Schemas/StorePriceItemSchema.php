<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StorePriceItem',
    description: 'Elemento del payload de POST /products/{id}/prices. product_id debe coincidir con el id de la ruta y no se admiten atributos fuera de ProductPrice::$fillable.',
    type: 'object',
    required: ['product_id', 'currency_id', 'price'],
    properties: [
        new OA\Property(property: 'product_id', description: 'Rule::in([id de la ruta])', type: 'integer', example: 1),
        new OA\Property(
            property: 'currency_id',
            description: 'exists:currencies,id, distinct en el lote y único por producto en product_prices.',
            type: 'integer',
            example: 2,
        ),
        new OA\Property(
            property: 'price',
            description: 'numeric|min:0|max:99999999.9999|decimal:0,4',
            type: 'number',
            format: 'float',
            example: 280.5,
        ),
    ],
)]
class StorePriceItemSchema {}
