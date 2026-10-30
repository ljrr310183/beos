<?php

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('registers a list of alternate currency prices', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();
    $ves = Currency::factory()->ves()->create();

    $response = $this->postJson("/api/products/{$product->id}/prices", [
        ['product_id' => $product->id, 'currency_id' => $eur->id, 'price' => 280.50],
        ['product_id' => $product->id, 'currency_id' => $ves->id, 'price' => 1200],
    ]);

    $response->assertCreated()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.product_id', $product->id)
        ->assertJsonPath('data.0.price', '280.5000')
        ->assertJsonPath('data.0.currency.id', $eur->id)
        ->assertJsonPath('data.1.currency.id', $ves->id);

    expect(ProductPrice::query()->where('product_id', $product->id)->count())->toBe(2);
});

it('rejects a duplicated currency for the same product without modifying the stored row', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();

    $product->prices()->create(['currency_id' => $eur->id, 'price' => '10.0000']);

    $this->postJson("/api/products/{$product->id}/prices", [
        ['product_id' => $product->id, 'currency_id' => $eur->id, 'price' => '99.0000'],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('0.currency_id');

    expect(ProductPrice::query()->where('product_id', $product->id)->sole()->price)->toBe('10.0000');
});

it('rejects duplicated currencies inside the same payload', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();

    $this->postJson("/api/products/{$product->id}/prices", [
        ['product_id' => $product->id, 'currency_id' => $eur->id, 'price' => '10.0000'],
        ['product_id' => $product->id, 'currency_id' => $eur->id, 'price' => '20.0000'],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('0.currency_id');

    expect(ProductPrice::query()->count())->toBe(0);
});

it('rolls back the whole batch when one element fails', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();

    $this->postJson("/api/products/{$product->id}/prices", [
        ['product_id' => $product->id, 'currency_id' => $eur->id, 'price' => '10.0000'],
        ['product_id' => $product->id, 'currency_id' => 99999, 'price' => '20.0000'],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('1.currency_id');

    expect(ProductPrice::query()->count())->toBe(0);
});

it('returns 404 when registering prices on a product that does not exist', function () {
    $eur = Currency::factory()->eur()->create();

    $this->postJson('/api/products/99999/prices', [
        ['product_id' => 99999, 'currency_id' => $eur->id, 'price' => '10.0000'],
    ])
        ->assertNotFound()
        ->assertJsonPath('error', 'NOT_FOUND');
});

it('rejects a product_id that does not match the route', function () {
    $product = Product::factory()->create();
    $other = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();

    $this->postJson("/api/products/{$product->id}/prices", [
        ['product_id' => $other->id, 'currency_id' => $eur->id, 'price' => '10.0000'],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('0.product_id');

    expect(ProductPrice::query()->count())->toBe(0);
});

it('rejects an unknown currency', function () {
    $product = Product::factory()->create();

    $this->postJson("/api/products/{$product->id}/prices", [
        ['product_id' => $product->id, 'currency_id' => 99999, 'price' => '10.0000'],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('0.currency_id');

    expect(ProductPrice::query()->count())->toBe(0);
});

it('accepts an explicit price in the product base currency', function () {
    $product = Product::factory()->create();

    $this->postJson("/api/products/{$product->id}/prices", [
        ['product_id' => $product->id, 'currency_id' => $product->currency_id, 'price' => '310.0000'],
    ])->assertCreated();

    $stored = ProductPrice::query()->where('product_id', $product->id)->sole();

    expect($stored->currency_id)->toBe($product->currency_id)
        ->and($stored->price)->toBe('310.0000');
});

it('rejects a price with more than four decimal places', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();

    $this->postJson("/api/products/{$product->id}/prices", [
        ['product_id' => $product->id, 'currency_id' => $eur->id, 'price' => 1.00001],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('0.price');

    expect(ProductPrice::query()->count())->toBe(0);
});

it('rejects an empty price list', function () {
    $product = Product::factory()->create();

    $this->postJson("/api/products/{$product->id}/prices", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('payload');
});

it('rejects a payload that is not a list', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();

    $this->postJson("/api/products/{$product->id}/prices", [
        'prices' => [
            ['product_id' => $product->id, 'currency_id' => $eur->id, 'price' => '10.0000'],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('payload');

    expect(ProductPrice::query()->count())->toBe(0);
});

it('rejects a single price object outside the list', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();

    $this->postJson("/api/products/{$product->id}/prices", [
        'product_id' => $product->id,
        'currency_id' => $eur->id,
        'price' => '10.0000',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('payload');

    expect(ProductPrice::query()->count())->toBe(0);
});

it('rejects attributes outside the product price fillable set', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();

    $this->postJson("/api/products/{$product->id}/prices", [
        [
            'product_id' => $product->id,
            'currency_id' => $eur->id,
            'price' => '10.0000',
            'created_at' => '2026-01-01 00:00:00',
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('0.payload');

    expect(ProductPrice::query()->count())->toBe(0);
});

it('registers a batch with a single price', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();

    $this->postJson("/api/products/{$product->id}/prices", [
        ['product_id' => $product->id, 'currency_id' => $eur->id, 'price' => 12.5],
    ])
        ->assertCreated()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.price', '12.5000');

    expect(ProductPrice::query()->where('product_id', $product->id)->count())->toBe(1);
});

it('rejects a list that mixes price objects with a scalar element', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();

    $this->postJson("/api/products/{$product->id}/prices", [
        ['product_id' => $product->id, 'currency_id' => $eur->id, 'price' => '10.0000'],
        'cadena',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('1');

    expect(ProductPrice::query()->count())->toBe(0);
});

it('accepts the maximum batch of 50 prices', function () {
    $product = Product::factory()->create();
    $currencies = Currency::factory()->count(50)->create();

    $payload = $currencies->values()->map(fn (Currency $currency) => [
        'product_id' => $product->id,
        'currency_id' => $currency->id,
        'price' => '10.0000',
    ])->all();

    $this->postJson("/api/products/{$product->id}/prices", $payload)
        ->assertCreated()
        ->assertJsonCount(50, 'data');

    expect(ProductPrice::query()->count())->toBe(50);
});

it('rejects a batch larger than 50 prices', function () {
    $product = Product::factory()->create();
    $currencies = Currency::factory()->count(51)->create();

    $payload = $currencies->values()->map(fn (Currency $currency) => [
        'product_id' => $product->id,
        'currency_id' => $currency->id,
        'price' => '10.0000',
    ])->all();

    $this->postJson("/api/products/{$product->id}/prices", $payload)
        ->assertUnprocessable()
        ->assertJsonPath('errors.payload.0', 'El listado de precios no puede superar los 50 elementos.');

    expect(ProductPrice::query()->count())->toBe(0);
});

it('reports a duplicate key raised by the database as a payload validation error', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();

    ProductPrice::creating(function (ProductPrice $price): void {
        DB::table('product_prices')->insert([
            'product_id' => $price->product_id,
            'currency_id' => $price->currency_id,
            'price' => '1.0000',
        ]);
    });

    $this->postJson("/api/products/{$product->id}/prices", [
        ['product_id' => $product->id, 'currency_id' => $eur->id, 'price' => '10.0000'],
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.payload.0', 'El precio para esta divisa ya ha sido registrado para este producto.');

    expect(ProductPrice::query()->count())->toBe(0);
});
