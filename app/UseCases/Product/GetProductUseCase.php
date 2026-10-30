<?php

namespace App\UseCases\Product;

use App\Models\Product;
use App\Repositories\ProductRepository;

class GetProductUseCase
{
    public function execute(int $id): ?Product
    {
        return ProductRepository::getById($id);
    }
}
