<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Applied tax rate ceiling: 0.1600 means 16%. No product is taxed above it.
     */
    public const MAX_TAX_RATE = 0.16;

    /**
     * Define the model's default state.
     *
     * Base currency defaults to the USD row already present in `currencies`:
     * per the catalog contract in the spec, product prices are quoted in USD
     * unless a state overrides the currency.
     *
     * `tax_cost` is the tax AMOUNT stored in the row, computed from a rate that
     * never exceeds MAX_TAX_RATE (16%). A rate of 0 yields an amount of 0, which
     * is how tax-exempt products are represented.
     *
     * The rate travels as a factory-only `tax_rate` attribute and the amount is
     * resolved in configure(), so overriding `price` keeps both consistent
     * regardless of state order.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 10, 5000);

        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => $this->money($price),
            'currency_id' => $this->usdCurrencyId(),
            'tax_rate' => fake()->randomFloat(4, 0.0, self::MAX_TAX_RATE),
            'manufacturing_cost' => $this->money(round($price * fake()->randomFloat(2, 0.2, 0.7), 2)),
        ];
    }

    /**
     * Derives `tax_cost` from the final `price`, then drops the factory-only
     * `tax_rate` so it never reaches the insert.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Product $product) {
            $rate = (float) ($product->tax_rate ?? 0.0);

            $product->tax_cost = $this->money(round((float) $product->price * $rate, 2));

            unset($product->tax_rate);
        });
    }

    /**
     * Explicit USD base currency. Same as the default state, spelled out where
     * the intent matters.
     */
    public function usd(): static
    {
        return $this->state(fn (array $attributes) => [
            'currency_id' => $this->usdCurrencyId(),
        ]);
    }

    /**
     * Base currency other than USD, used by the conversion scenarios.
     */
    public function forCurrency(int $currencyId): static
    {
        return $this->state(fn (array $attributes) => [
            'currency_id' => $currencyId,
        ]);
    }

    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => null,
        ]);
    }

    public function priced(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->money($price),
        ]);
    }

    /**
     * Tax exempt product: the applied rate is 0, so the stored tax amount is 0.
     */
    public function taxExempt(): static
    {
        return $this->taxable(0.0);
    }

    /**
     * Explicit tax rate, clamped to 0 - MAX_TAX_RATE (0.16). The stored value is
     * the resulting amount, resolved from the final price in configure().
     */
    public function taxable(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_rate' => max(0.0, min($rate, self::MAX_TAX_RATE)),
        ]);
    }

    /**
     * Resolves the USD row without duplicating it: the seeder owns the catalog,
     * the factory only reads from it. Falls back to creating the USD row when
     * the catalog was not seeded.
     */
    private function usdCurrencyId(): int
    {
        $id = Currency::query()->where('name', 'US Dollar')->value('id');

        return $id ?? Currency::factory()->usd()->create()->id;
    }

    private function money(float $value): string
    {
        return number_format($value, 4, '.', '');
    }
}
