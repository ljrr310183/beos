<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * The seeded currency catalog, in the order the spec fixtures rely on.
     *
     * Order is the contract, not the ids: a seeder looping this list on a fresh
     * schema yields 1 = USD, 2 = EUR, 3 = VES, 4 = ARS, 5 = CHF. MySQL keeps
     * the AUTO_INCREMENT counter after a delete(), so any prior insert on the
     * same database shifts them.
     *
     * @var list<string>
     */
    public const CATALOG = ['usd', 'eur', 'ves', 'ars', 'chf'];

    /**
     * Source of the seeded rates: `GET https://api.frankfurter.dev/v2/rates?base=USD`,
     * pulled on 2026-10-29. Frankfurter aggregates ECB reference rates, so these are
     * banking reference rates with USD as the base currency.
     *
     * Raw API values (more precision than `decimal(12, 4)` can store):
     * EUR 0.89647, VES 884.2, ARS 1539.18, CHF 0.82897.
     *
     * @var array<string, string>
     */
    public const RATES = [
        'usd' => '1.0000',
        'eur' => '0.8965',
        'ves' => '884.2000',
        'ars' => '1539.1800',
        'chf' => '0.8290',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'symbol' => strtoupper(fake()->lexify('???')),
            'exchange_rate' => $this->rate(fake()->randomFloat(4, 0.01, 5000)),
        ];
    }

    public function usd(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => self::RATES['usd'],
        ]);
    }

    public function eur(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Euro',
            'symbol' => '€',
            'exchange_rate' => self::RATES['eur'],
        ]);
    }

    public function ves(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Venezuelan Bolívar',
            'symbol' => 'Bs.',
            'exchange_rate' => self::RATES['ves'],
        ]);
    }

    public function ars(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Argentine Peso',
            'symbol' => 'ARS$',
            'exchange_rate' => self::RATES['ars'],
        ]);
    }

    public function chf(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Swiss Franc',
            'symbol' => 'CHF',
            'exchange_rate' => self::RATES['chf'],
        ]);
    }

    /**
     * Non-positive rate, used to assert conversion rejection (spec UC-CA 1.12.4).
     */
    public function zeroRate(): static
    {
        return $this->state(fn (array $attributes) => [
            'exchange_rate' => '0.0000',
        ]);
    }

    private function rate(float $value): string
    {
        return number_format($value, 4, '.', '');
    }
}
