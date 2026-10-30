<?php

namespace App\UseCases\Product;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Repositories\ProductRepository;
use Illuminate\Support\Collection;

class GetPricesPerProductUseCase
{
    /**
     * Return every price of the product, including the base price held by the
     * products row when that currency has no explicit product_prices entry.
     *
     * @return Collection<int, ProductPrice>|null
     */
    public function execute(int $id): ?Product
    {
        $product = ProductRepository::getByPricesPerProduct($id);

        return $product;
    }
}
