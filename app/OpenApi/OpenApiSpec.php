<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Catálogo de Productos y Precios Multidivisa',
    description: 'API REST del catálogo de productos. Cada producto tiene un precio base expresado en su divisa principal y una lista opcional de precios alternativos por divisa.',
)]
#[OA\Server(
    url: '/api',
    description: 'Prefijo aplicado por routes/api.php.',
)]
#[OA\Tag(
    name: 'Products',
    description: 'Productos y sus precios por divisa.',
)]
class OpenApiSpec {}
