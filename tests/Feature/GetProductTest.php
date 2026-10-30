<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a product with its nested currency', function () {
    $product = Product::factory()->create();

    $response = $this->getJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'description',
                'price',
                'tax_cost',
                'manufacturing_cost',
                'currency' => ['id', 'name', 'symbol', 'exchange_rate'],
                'created_at',
                'updated_at',
            ],
        ]);

    expect($response->json('data'))
        ->not->toHaveKey('currency_id')
        ->and($response->json('data.currency.id'))->toBe($product->currency_id);
});

it('returns 404 when the product does not exist', function () {
    $this->getJson('/api/products/99999')
        ->assertNotFound()
        ->assertExactJson([
            'message' => 'Producto no encontrado.',
            'error' => 'NOT_FOUND',
        ]);
});

it('returns 404 when the route id is not numeric', function () {
    $this->getJson('/api/products/abc')
        ->assertNotFound()
        ->assertJsonPath('error', 'NOT_FOUND');
});

it('keeps the json envelope when the accept header is missing', function () {
    $response = $this->get('/api/products/99999');

    $response->assertNotFound()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('error', 'NOT_FOUND');
});
