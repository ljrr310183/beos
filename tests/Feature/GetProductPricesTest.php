<?php

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the base price followed by the alternate prices of the product', function () {
    $product = Product::factory()->priced(150)->create();
    $eur = Currency::factory()->eur()->create();
    $ves = Currency::factory()->ves()->create();

    $product->prices()->create(['currency_id' => $eur->id, 'price' => '300.0000']);
    $product->prices()->create(['currency_id' => $ves->id, 'price' => '260.0000']);

    $response = $this->getJson("/api/products/{$product->id}/prices");

    $response->assertOk()
        ->assertJsonCount(3, 'data.price_list')
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.price_list.0.id', 0)
        ->assertJsonPath('data.price_list.0.product_id', $product->id)
        ->assertJsonPath('data.price_list.0.currency_id', $product->currency_id)
        ->assertJsonPath('data.price_list.0.is_base_price', true)
        ->assertJsonPath('data.price_list.1.currency_id', $eur->id)
        ->assertJsonPath('data.price_list.1.is_base_price', false)
        ->assertJsonPath('data.price_list.1.currency.name', 'Euro')
        ->assertJsonPath('data.price_list.1.currency.symbol', '€')
        ->assertJsonPath('data.price_list.2.currency_id', $ves->id)
        ->assertJsonPath('data.price_list.2.is_base_price', false)
        ->assertJsonPath('data.price_list.2.currency.name', 'Venezuelan Bolívar');

    expect((float) $response->json('data.price_list.0.price'))->toBe(150.0)
        ->and((float) $response->json('data.price_list.1.price'))->toBe(300.0)
        ->and((float) $response->json('data.price_list.2.price'))->toBe(260.0)
        ->and($response->json('data'))->not->toHaveKey('currency')
        ->and($response->json('data'))->not->toHaveKey('created_at')
        ->and($response->json('data'))->not->toHaveKey('updated_at');
});

it('returns only the base price when the product has no alternate prices', function () {
    $product = Product::factory()->priced(150)->create();

    $this->getJson("/api/products/{$product->id}/prices")
        ->assertOk()
        ->assertJsonCount(1, 'data.price_list')
        ->assertJsonPath('data.price_list.0.id', 0)
        ->assertJsonPath('data.price_list.0.is_base_price', true);
});

it('does not return prices belonging to other products', function () {
    $product = Product::factory()->create();
    $other = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();
    $ves = Currency::factory()->ves()->create();
    $ars = Currency::factory()->ars()->create();

    $product->prices()->create(['currency_id' => $eur->id, 'price' => '300.0000']);
    $other->prices()->create(['currency_id' => $ves->id, 'price' => '260.0000']);
    $other->prices()->create(['currency_id' => $ars->id, 'price' => '1200.0000']);

    $response = $this->getJson("/api/products/{$product->id}/prices");

    $response->assertOk()
        ->assertJsonCount(2, 'data.price_list');

    collect($response->json('data.price_list'))->each(function (array $row) use ($product): void {
        expect($row['product_id'])->toBe($product->id);
    });
});

it('returns 404 when the product does not exist', function () {
    $this->getJson('/api/products/99999/prices')
        ->assertNotFound()
        ->assertExactJson([
            'message' => 'Producto no encontrado.',
            'error' => 'NOT_FOUND',
        ]);
});
