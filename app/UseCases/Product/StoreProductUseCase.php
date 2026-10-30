<?php

namespace App\UseCases\Product;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class StoreProductUseCase
{
    /**
     * Creates a product together with its alternate-currency prices.
     *
     * `products_name_unique` is the last line of defence: two concurrent
     * requests can both clear Rule::unique, so the duplicate key becomes the
     * same 422 the validator would have produced instead of a 500.
     */
    public function execute(StoreProductRequest $request): Product
    {
        $data = $request->validated();

        try {
            return ProductRepository::createWithPrices(
                Arr::except($data, ['prices']),
                $data['prices'] ?? []
            );
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            throw ValidationException::withMessages([
                'name' => 'Ya existe un producto con ese nombre.',
            ]);
        }
    }
}
