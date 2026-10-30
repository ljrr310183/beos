<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Product',
    description: 'Producto según App\Http\Resources\ProductResource (sin el bloque price_list).',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Wireless Keyboard'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Teclado inalámbrico con retroiluminación.'),
        new OA\Property(
            property: 'price',
            description: 'Cast decimal:4 del modelo Product, por eso viaja como string.',
            type: 'string',
            example: '99.9900',
        ),
        new OA\Property(property: 'tax_cost', type: 'string', example: '12.5000'),
        new OA\Property(property: 'manufacturing_cost', type: 'string', example: '45.0000'),
        new OA\Property(property: 'currency', ref: '#/components/schemas/Currency'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class ProductSchema {}
