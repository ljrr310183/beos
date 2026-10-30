<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductList',
    description: 'AnonymousResourceCollection de ProductResource: envelope del paginador de Laravel con page y per_page.',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Product'),
        ),
        new OA\Property(
            property: 'links',
            type: 'object',
            properties: [
                new OA\Property(property: 'first', type: 'string', nullable: true, example: 'http://localhost:8000/api/products?page=1'),
                new OA\Property(property: 'last', type: 'string', nullable: true, example: 'http://localhost:8000/api/products?page=2'),
                new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
                new OA\Property(property: 'next', type: 'string', nullable: true, example: 'http://localhost:8000/api/products?page=2'),
            ],
        ),
        new OA\Property(
            property: 'meta',
            type: 'object',
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                new OA\Property(property: 'from', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'last_page', type: 'integer', example: 2),
                new OA\Property(
                    property: 'links',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'url', type: 'string', nullable: true),
                            new OA\Property(property: 'label', type: 'string', example: '1'),
                            new OA\Property(property: 'page', type: 'integer', nullable: true, example: 1),
                            new OA\Property(property: 'active', type: 'boolean', example: true),
                        ],
                    ),
                ),
                new OA\Property(property: 'path', type: 'string', example: 'http://localhost:8000/api/products'),
                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                new OA\Property(property: 'to', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'total', type: 'integer', example: 25),
            ],
        ),
    ],
)]
class ProductListSchema {}
