<?php

namespace App\UseCases\Product;

use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Support\Arr;

class UpdateProductUseCase
{
    public function execute(Product $product, UpdateProductRequest $request): Product
    {
        $data = $request->validated();

        return ProductRepository::update(
            $product,
            Arr::except($data, ['prices']),
            array_key_exists('prices', $data) ? $data['prices'] : null
        );
    }
}
