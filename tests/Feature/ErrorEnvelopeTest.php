<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('returns the standard envelope when the http method is not allowed', function () {
    Product::factory()->create();

    $this->patchJson('/api/products/1')
        ->assertStatus(405)
        ->assertJsonPath('error', 'METHOD_NOT_ALLOWED')
        ->assertJsonPath('message', 'El método HTTP no está soportado para esta ruta.');
});

it('always includes a non empty errors object on validation failures', function () {
    $response = $this->postJson('/api/products', []);

    $response->assertUnprocessable()
        ->assertJsonPath('error', 'VALIDATION_ERROR')
        ->assertJsonPath('message', 'Los datos proporcionados no son válidos.');

    expect($response->json('errors'))->toBeArray()->not->toBeEmpty();
});

it('hides the trace of an unhandled exception in production', function () {
    Route::get('/api/_boom', fn () => throw new RuntimeException('boom'));
    config(['app.debug' => false]);

    $response = $this->getJson('/api/_boom');

    $response->assertStatus(500)
        ->assertJsonPath('error', 'SERVER_ERROR')
        ->assertJsonPath('message', 'Error interno del servidor.')
        ->assertJsonMissingPath('exception')
        ->assertJsonMissingPath('trace');
});

it('ignores unknown query parameters', function () {
    Product::factory()->count(2)->create();

    $this->getJson('/api/products?foo=bar&page=1')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});

it('rejects an api uri that carries a fragment delimiter', function () {
    $original = $_SERVER['REQUEST_URI'] ?? null;
    $_SERVER['REQUEST_URI'] = '/api/products/#junk/prices';

    try {
        $this->getJson('/api/products')
            ->assertStatus(400)
            ->assertJsonPath('error', 'BAD_REQUEST')
            ->assertJsonPath('message', 'URI malformada.');
    } finally {
        if ($original === null) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $original;
        }
    }
});
