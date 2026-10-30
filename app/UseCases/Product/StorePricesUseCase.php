<?php

namespace App\UseCases\Product;

use App\Http\Requests\StorePriceRequest;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class StorePricesUseCase
{
    /**
     * Registers the alternate-currency prices sent as a bare list.
     *
     * Persistence lives in the repository, which keeps the batch atomic and
     * never overwrites an existing row. `uk_product_currency` is the last line
     * of defence, because two concurrent requests can both clear
     * Rule::unique; the duplicate key becomes the same 422 the validator
     * would have produced.
     *
     * @return Collection<int, ProductPrice>
     */
    public function execute(Product $product, StorePriceRequest $request): Collection
    {
        try {
            $created = ProductRepository::createPrices($product, $request->validated());
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            throw ValidationException::withMessages([
                'payload' => 'El precio para esta divisa ya ha sido registrado para este producto.',
            ]);
        }

        return $created->load('currency');
    }
}
