<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the paginated list of products', function () {
    Product::factory()->count(25)->create();

    $response = $this->getJson('/api/products?page=1&per_page=15');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
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
            ],
            'links',
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ])
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonPath('meta.total', 25)
        ->assertJsonCount(15, 'data');

    collect($response->json('data'))->each(function (array $product): void {
        expect($product)->not->toHaveKey('currency_id');
    });
});

it('returns an empty paginated list when there are no products', function () {
    $this->getJson('/api/products')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});

it('normalises a page below one to the first page', function () {
    Product::factory()->count(20)->create();

    $this->getJson('/api/products?page=-3&per_page=10')
        ->assertOk()
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonCount(10, 'data');
});

it('falls back to the default page size when per_page is out of range', function () {
    Product::factory()->count(20)->create();

    $this->getJson('/api/products?per_page=0')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 15);

    $this->getJson('/api/products?per_page=101')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 15);
});

it('returns an empty data set when the page is beyond the last one', function () {
    Product::factory()->count(3)->create();

    $this->getJson('/api/products?page=5&per_page=15')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.current_page', 5);
});
