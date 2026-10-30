<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    private const DEFAULT_PAGE_SIZE = 15;

    private const MAX_PAGE_SIZE = 100;

    public static function getAll(Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));
        $pageSize = (int) $request->query('per_page', self::DEFAULT_PAGE_SIZE);
        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('direction', 'desc');

        if ($pageSize < 1 || $pageSize > self::MAX_PAGE_SIZE) {
            $pageSize = self::DEFAULT_PAGE_SIZE;
        }

        return Product::with('currency')
            ->orderBy($sort, $direction)
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    public static function getById(int $id): ?Product
    {
        return Product::with('currency')->find($id);
    }

    public static function getByPricesPerProduct(int $id): ?Product
    {
        $product = Product::with([
            'prices' => fn ($query) => $query->orderBy('id'),
            'prices.currency',
        ])
            ->find($id);

        if (! $product) {
            return null;
        }
        $prices = [
            'id' => 0,
            'product_id' => $product->id,
            'currency_id' => $product->currency_id,
            'price' => (float) $product->price,
            'is_base_price' => true,
        ];

        $productPrices = $product->prices->map(function ($price) {
            return [
                'id' => $price->id,
                'product_id' => $price->product_id,
                'currency_id' => $price->currency_id,
                'price' => (float) $price->price,
                'currency' => [
                    'name' => $price->currency->name,
                    'symbol' => $price->currency->symbol,
                ],
                'is_base_price' => false,
            ];
        })->toArray();
        $prices = array_merge([$prices], $productPrices);

        $product->setAttribute('price_list', collect($prices)->sortByDesc('is_base_price')->map(function ($price) {
            return (object) $price;
        })->toArray());

        return $product;
    }

    /**
     * Creates the product and its prices as a single unit of work.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array{currency_id: int|string, price: int|float|string}>  $prices
     */
    public static function createWithPrices(array $data, array $prices = []): Product
    {
        return DB::transaction(function () use ($data, $prices): Product {
            $product = Product::create($data);
            self::syncPrices($product, $prices);

            return $product;
        });
    }

    /**
     * Replaces the product and, when `$prices` is given, its whole price list.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array{currency_id: int|string, price: int|float|string}>|null  $prices
     *                                                                                            Null leaves the existing prices untouched; an array replaces them as a set.
     */
    public static function update(Product $product, array $data, ?array $prices = null): Product
    {
        return DB::transaction(function () use ($product, $data, $prices): Product {
            $product->update($data);

            if ($prices !== null) {
                $product->prices()->delete();
                self::syncPrices($product, $prices);
            }

            return $product->refresh();
        });
    }

    /**
     * Inserts the given prices for the product as a single unit of work.
     *
     * Unlike syncPrices(), an existing (product_id, currency_id) row is never
     * overwritten: the `uk_product_currency` index makes the whole batch fail
     * instead, which is what POST /products/{id}/prices must report.
     *
     * @param  array<int, array{product_id?: int|string, currency_id: int|string, price: int|float|string}>  $prices
     * @return Collection<int, ProductPrice>
     */
    public static function createPrices(Product $product, array $prices): Collection
    {
        $rows = array_map(static fn (array $price): array => [
            'product_id' => $product->id,
            'currency_id' => (int) $price['currency_id'],
            'price' => $price['price'],
        ], $prices);

        return DB::transaction(
            fn (): Collection => $product->prices()->createMany($rows)
        );
    }

    /**
     * @param  array<int, array{currency_id: int|string, price: int|float|string}>  $prices
     */
    public static function syncPrices(Product $product, array $prices): void
    {
        if ($prices === []) {
            return;
        }

        $rows = array_map(static fn (array $price): array => [
            'currency_id' => (int) $price['currency_id'],
            'price' => $price['price'],
        ], $prices);

        $product->prices()->upsert($rows, ['product_id', 'currency_id'], ['price']);
    }
}
