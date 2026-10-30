<?php

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function productPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Wireless Keyboard',
        'description' => 'Mechanical keyboard with wireless dongle',
        'price' => '99.9900',
        'currency_id' => Currency::factory()->usd()->create()->id,
        'tax_cost' => '16.0000',
        'manufacturing_cost' => '45.5000',
    ], $overrides);
}

it('creates a product without prices', function () {
    $response = $this->postJson('/api/products', productPayload());

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Wireless Keyboard')
        ->assertJsonPath('data.price', '99.9900');

    expect(Product::query()->count())->toBe(1)
        ->and(Product::query()->first()->prices()->count())->toBe(0);
});

it('creates a product with prices in a single transaction', function () {
    $eur = Currency::factory()->eur()->create();

    $response = $this->postJson('/api/products', productPayload([
        'prices' => [
            ['currency_id' => $eur->id, 'price' => '89.5000'],
        ],
    ]));

    $response->assertCreated();

    $product = Product::query()->firstOrFail();

    expect($product->prices()->count())->toBe(1)
        ->and($product->prices()->first()->currency_id)->toBe($eur->id)
        ->and($product->prices()->first()->price)->toBe('89.5000');
});

it('rejects a duplicate product name', function () {
    $this->postJson('/api/products', productPayload())->assertCreated();

    $this->postJson('/api/products', productPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('rejects an unknown price currency and persists nothing', function () {
    $this->postJson('/api/products', productPayload([
        'prices' => [
            ['currency_id' => 999999, 'price' => '10.0000'],
        ],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('prices.0.currency_id');

    expect(Product::query()->count())->toBe(0);
});

it('rejects duplicate currencies inside prices', function () {
    $eur = Currency::factory()->eur()->create();

    $this->postJson('/api/products', productPayload([
        'prices' => [
            ['currency_id' => $eur->id, 'price' => '10.0000'],
            ['currency_id' => $eur->id, 'price' => '11.0000'],
        ],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('prices.1.currency_id');
});

it('rejects non array prices entries', function () {
    $this->postJson('/api/products', productPayload([
        'prices' => ['not-an-array'],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('prices.0');
});

it('rejects invalid required fields reporting one error key per field', function () {
    $response = $this->postJson('/api/products', [
        'name' => '',
        'price' => -10.50,
        'tax_cost' => 'invalido',
        'manufacturing_cost' => -5.00,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'price', 'tax_cost', 'manufacturing_cost']);

    expect(Product::query()->count())->toBe(0);
});

it('rejects a base currency that does not exist', function () {
    $this->postJson('/api/products', productPayload(['currency_id' => 99999]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('currency_id');

    expect(Product::query()->count())->toBe(0);
});

it('rejects a price with more than four decimal places', function () {
    $this->postJson('/api/products', productPayload(['price' => 10.12345]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('price');

    expect(Product::query()->count())->toBe(0);
});

it('rejects a price above the column maximum', function () {
    $this->postJson('/api/products', productPayload(['price' => 100000000]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('price');

    expect(Product::query()->count())->toBe(0);
});

it('reports the spanish duplicate name message and leaves the existing product untouched', function () {
    $this->postJson('/api/products', productPayload())->assertCreated();

    $original = Product::query()->sole();

    $response = $this->postJson('/api/products', productPayload(['price' => '11.0000']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    expect($response->json('errors.name.0'))->toBe('El nombre del producto ya ha sido registrado.')
        ->and(Product::query()->count())->toBe(1)
        ->and(Product::query()->sole()->price)->toBe($original->price);
});

it('trims the name before checking uniqueness', function () {
    $this->postJson('/api/products', productPayload())->assertCreated();

    $this->postJson('/api/products', productPayload(['name' => 'Wireless Keyboard ']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    expect(Product::query()->count())->toBe(1);
});

it('persists a numeric string price normalised to four decimals', function () {
    $response = $this->postJson('/api/products', productPayload(['price' => '99.99']));

    $response->assertCreated()
        ->assertJsonPath('data.price', '99.9900');

    expect(Product::query()->sole()->price)->toBe('99.9900');
});

it('ignores undeclared fields in the payload', function () {
    $response = $this->postJson('/api/products', productPayload([
        'id' => 999,
        'created_at' => '2000-01-01T00:00:00Z',
    ]));

    $response->assertCreated();

    expect($response->json('data.id'))->not->toBe(999)
        ->and($response->json('data.created_at'))->not->toBe('2000-01-01T00:00:00.000000Z');
});
