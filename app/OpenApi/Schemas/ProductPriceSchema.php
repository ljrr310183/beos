<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductPrice',
    description: 'Registro de product_prices según App\Http\Resources\ProductPriceResource.',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'product_id', type: 'integer', example: 1),
        new OA\Property(
            property: 'price',
            description: 'Cast decimal:4 del modelo ProductPrice, por eso viaja como string.',
            type: 'string',
            example: '280.5000',
        ),
        new OA\Property(property: 'currency', ref: '#/components/schemas/Currency'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class ProductPriceSchema {}
