<?php

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function updatePayload(array $overrides = []): array
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

function existingProduct(array $overrides = []): Product
{
    return Product::factory()->create($overrides);
}

it('replaces every field of an existing product', function () {
    $product = existingProduct();
    $eur = Currency::factory()->eur()->create();

    $response = $this->putJson("/api/products/{$product->id}", [
        'name' => 'Wireless Keyboard Pro',
        'description' => 'Updated description',
        'price' => '120.5000',
        'currency_id' => $eur->id,
        'tax_cost' => '20.0000',
        'manufacturing_cost' => '50.2500',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Wireless Keyboard Pro')
        ->assertJsonPath('data.price', '120.5000')
        ->assertJsonPath('data.currency.id', $eur->id)
        ->assertJsonMissingPath('data.currency_id');

    $product->refresh();

    expect($product->name)->toBe('Wireless Keyboard Pro')
        ->and($product->description)->toBe('Updated description')
        ->and($product->price)->toBe('120.5000')
        ->and($product->currency_id)->toBe($eur->id)
        ->and($product->tax_cost)->toBe('20.0000')
        ->and($product->manufacturing_cost)->toBe('50.2500');
});

it('accepts a null description', function () {
    $product = existingProduct();

    $this->putJson("/api/products/{$product->id}", updatePayload(['description' => null]))
        ->assertOk()
        ->assertJsonPath('data.description', null);

    expect($product->refresh()->description)->toBeNull();
});

it('returns 404 when the product does not exist', function () {
    $this->putJson('/api/products/99999', updatePayload())
        ->assertNotFound()
        ->assertJsonPath('error', 'NOT_FOUND')
        ->assertJsonPath('message', 'Producto no encontrado.');
});

it('rejects an invalid price and an unknown currency without touching the row', function () {
    $product = existingProduct();
    $original = $product->only(['name', 'price', 'currency_id', 'tax_cost', 'manufacturing_cost']);

    $this->putJson("/api/products/{$product->id}", updatePayload([
        'price' => '-1',
        'currency_id' => 99999,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['price', 'currency_id']);

    expect($product->refresh()->only(['name', 'price', 'currency_id', 'tax_cost', 'manufacturing_cost']))->toBe($original);
});

it('rejects a payload missing a required field without touching the row', function () {
    $product = existingProduct();
    $payload = updatePayload();
    unset($payload['manufacturing_cost']);

    $this->putJson("/api/products/{$product->id}", $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('manufacturing_cost');

    expect($product->refresh()->manufacturing_cost)->not->toBeNull();
});

it('rejects a payload with the description key omitted', function () {
    $product = existingProduct();
    $payload = updatePayload();
    unset($payload['description']);

    $this->putJson("/api/products/{$product->id}", $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('description');
});

it('does not support patch', function () {
    $product = existingProduct();

    $this->patchJson("/api/products/{$product->id}", updatePayload())
        ->assertStatus(405)
        ->assertJsonPath('error', 'METHOD_NOT_ALLOWED');
});

it('accepts its own name but rejects the name of another product', function () {
    $product = existingProduct(['name' => 'Wireless Keyboard']);
    $other = existingProduct(['name' => 'Wired Mouse']);

    $this->putJson("/api/products/{$product->id}", updatePayload(['name' => 'Wireless Keyboard']))
        ->assertOk()
        ->assertJsonPath('data.name', 'Wireless Keyboard');

    $this->putJson("/api/products/{$product->id}", updatePayload(['name' => $other->name]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('replaces the whole price list when prices are sent', function () {
    $product = existingProduct();
    $usd = Currency::factory()->usd()->create();
    $eur = Currency::factory()->eur()->create();
    $chf = Currency::factory()->chf()->create();

    $product->prices()->createMany([
        ['currency_id' => $eur->id, 'price' => '10.0000'],
        ['currency_id' => $chf->id, 'price' => '11.0000'],
    ]);

    $this->putJson("/api/products/{$product->id}", updatePayload([
        'prices' => [
            ['currency_id' => $usd->id, 'price' => '88.0000'],
        ],
    ]))->assertOk();

    $prices = $product->prices()->get();

    expect($prices)->toHaveCount(1)
        ->and($prices->first()->currency_id)->toBe($usd->id)
        ->and($prices->first()->price)->toBe('88.0000');
});

it('leaves the price list untouched when prices are not sent', function () {
    $product = existingProduct();
    $eur = Currency::factory()->eur()->create();

    $product->prices()->create(['currency_id' => $eur->id, 'price' => '10.0000']);

    $this->putJson("/api/products/{$product->id}", updatePayload())->assertOk();

    expect($product->prices()->count())->toBe(1);
});
