<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Seeder;
use RuntimeException;

class ProductPriceSeeder extends Seeder
{
    /**
     * Reference currency of the catalog. Product prices are quoted in USD by
     * default, so a USD row would only echo `products.price`.
     */
    private const REFERENCE_CURRENCY = 'US Dollar';

    /**
     * Seeds one row per product and non-USD currency, priced by converting the
     * product's own base price into the target currency.
     *
     * The conversion belongs to ProductPriceFactory (spec formula, line 656), so
     * the factory builds the draft and this seeder only persists it. That keeps a
     * single implementation of the arithmetic.
     *
     * Rows in the product's own base currency are skipped: the conversion is the
     * identity there, so the row would duplicate `products.price` and read as an
     * explicit override.
     *
     * Idempotent on `(product_id, currency_id)` — the pair behind
     * `uk_product_currency` — and the price is refreshed on every run so it never
     * drifts from `products.price`.
     */
    public function run(): void
    {
        $products = Product::query()->orderBy('id')->get();

        if ($products->isEmpty()) {
            throw new RuntimeException(
                'ProductPriceSeeder found no products. Run ProductSeeder first: every price is derived from a product base price.'
            );
        }

        $currencies = Currency::query()
            ->where('name', '!=', self::REFERENCE_CURRENCY)
            ->orderBy('id')
            ->get();

        foreach ($products as $product) {
            foreach ($currencies as $currency) {
                if ($currency->id === $product->currency_id) {
                    continue;
                }

                $draft = ProductPrice::factory()
                    ->forProduct($product)
                    ->forCurrency($currency)
                    ->make();

                ProductPrice::query()->updateOrCreate(
                    [
                        'product_id' => $draft->product_id,
                        'currency_id' => $draft->currency_id,
                    ],
                    ['price' => $draft->price],
                );
            }
        }
    }
}
