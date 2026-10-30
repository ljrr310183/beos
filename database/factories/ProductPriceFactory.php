<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPrice>
 */
class ProductPriceFactory extends Factory
{
    /**
     * Currency ids already claimed per product, tracked in memory because a
     * `count(n)` run builds every model before storing any of them: the database
     * cannot reveal the pairs picked earlier in the same batch.
     *
     * @var array<int, list<int>>
     */
    private array $reserved = [];

    /**
     * Product and currency are resolved in `afterMaking`, once the ids are known:
     * the price is a conversion of the product's base price into the target
     * currency, so it cannot be computed before the product exists.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
        ];
    }

    /**
     * Resolves `currency_id` and `price` with the spec conversion formula:
     * `round(products.price / rate_base * rate_target, 4, HALF_UP)`.
     *
     * Explicit values always win, so a caller-supplied currency or price is never
     * overwritten.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ProductPrice $productPrice) {
            $productPrice->currency_id ??= $this->freeCurrencyId($productPrice->product_id);

            $productPrice->price ??= $this->convertedPrice($productPrice->product_id, $productPrice->currency_id);
        });
    }

    /**
     * Explicit product. The target currency stays a non-USD currency of the
     * catalog unless `forCurrency()` overrides it.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }

    public function forCurrency(int|Currency $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency_id' => $currency instanceof Currency ? $currency->id : $currency,
        ]);
    }

    /**
     * Explicit target price, bypassing the conversion.
     */
    public function priced(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => number_format($price, 4, '.', ''),
        ]);
    }

    /**
     * Price in the product's own base currency. The spec accepts this row as an
     * explicit price, and it is not synced with `products.price`.
     *
     * Registered as its own `afterMaking` so it overrides the default currency
     * pick regardless of the order states were chained. Each product has exactly
     * one base-currency row, so combining it with `count(n > 1)` violates
     * `uk_product_currency`.
     */
    public function baseCurrency(): static
    {
        return $this->afterMaking(function (ProductPrice $productPrice) {
            $productPrice->currency_id = Product::query()->findOrFail($productPrice->product_id)->currency_id;
            $productPrice->price = $this->convertedPrice($productPrice->product_id, $productPrice->currency_id);
        });
    }

    /**
     * Picks a non-USD currency not yet priced for this product, so repeated rows
     * for one product never collide with `uk_product_currency`. Falls back to
     * creating a fresh catalog currency when every one is already used.
     */
    private function freeCurrencyId(int $productId): int
    {
        $baseCurrencyId = Product::query()->findOrFail($productId)->currency_id;

        $used = $this->reserved[$productId] ??= ProductPrice::query()
            ->where('product_id', $productId)
            ->pluck('currency_id')
            ->push($baseCurrencyId)
            ->all();

        $free = Currency::query()
            ->whereKeyNot($used)
            ->where('name', '!=', 'US Dollar')
            ->inRandomOrder()
            ->value('id');

        $currencyId = $free ?? $this->newNonUsdCurrency();
        $this->reserved[$productId][] = $currencyId;

        return $currencyId;
    }

    /**
     * Extra currency for when the catalog is exhausted. The name is suffixed so
     * it never collides with a catalog name: `currencies` has no UNIQUE on `name`
     * and lookups such as ProductFactory::usdCurrencyId() resolve by name.
     */
    private function newNonUsdCurrency(): int
    {
        $states = array_values(array_diff(CurrencyFactory::CATALOG, ['usd']));

        $currency = Currency::factory()->{$states[array_rand($states)]}()->make();
        $currency->name = substr($currency->name, 0, 90).' '.fake()->unique()->numerify('##');
        $currency->save();

        return $currency->id;
    }

    private function convertedPrice(int $productId, int $currencyId): string
    {
        $product = Product::query()->findOrFail($productId);
        $baseRate = (float) Currency::query()->findOrFail($product->currency_id)->exchange_rate;
        $targetRate = (float) Currency::query()->findOrFail($currencyId)->exchange_rate;

        return number_format(round((float) $product->price / $baseRate * $targetRate, 4, PHP_ROUND_HALF_UP), 4, '.', '');
    }
}
