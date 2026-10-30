<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('throttles the api group once the per minute allowance is exhausted', function () {
    Product::factory()->create();

    foreach (range(1, 60) as $attempt) {
        $this->getJson('/api/products')->assertOk();
    }

    $response = $this->getJson('/api/products');

    $response->assertStatus(429)
        ->assertJsonPath('error', 'TOO_MANY_REQUESTS')
        ->assertJsonPath('message', 'Demasiadas solicitudes. Inténtalo de nuevo en unos segundos.');

    expect($response->headers->has('Retry-After'))->toBeTrue();
});

it('applies the same allowance to every route of the api group', function () {
    Product::factory()->create();

    foreach (range(1, 60) as $attempt) {
        $this->getJson('/api/products')->assertOk();
    }

    $this->getJson('/api/products/1')->assertStatus(429);
});
