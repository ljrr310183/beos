<?php

namespace App\UseCases\Product;

use App\Models\Product;

class DeleteProductUseCase
{
    public function execute(Product $product): void
    {
        $product->delete();
    }
}
