<?php

namespace Database\Seeders;

use App\Models\Currency;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Seeds the 5-currency catalog in the order declared by CurrencyFactory::CATALOG,
     * so a fresh schema yields the deterministic ids 1-5 that the spec fixtures use.
     *
     * Idempotent by `name`: `currencies` has no UNIQUE index on `name` or `symbol`,
     * so running the seeder twice would otherwise duplicate every row silently.
     */
    public function run(): void
    {
        foreach (CurrencyFactory::CATALOG as $state) {
            $currency = Currency::factory()->{$state}()->make();

            Currency::query()->updateOrCreate(
                ['name' => $currency->name],
                [
                    'symbol' => $currency->symbol,
                    'exchange_rate' => $currency->exchange_rate,
                ],
            );
        }
    }
}
