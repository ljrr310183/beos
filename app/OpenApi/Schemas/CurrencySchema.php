<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Currency',
    description: 'Divisa según App\Http\Resources\CurrencyResource.',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'US Dollar'),
        new OA\Property(property: 'symbol', type: 'string', maxLength: 10, example: '$'),
        new OA\Property(
            property: 'exchange_rate',
            description: 'Cast decimal:4 del modelo Currency, por eso viaja como string.',
            type: 'string',
            example: '1.0000',
        ),
    ],
)]
class CurrencySchema {}
