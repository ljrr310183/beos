<?php

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes the product and cascades its prices', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();
    $chf = Currency::factory()->chf()->create();

    $product->prices()->createMany([
        ['currency_id' => $eur->id, 'price' => '10.0000'],
        ['currency_id' => $chf->id, 'price' => '11.0000'],
    ]);

    $this->deleteJson("/api/products/{$product->id}")
        ->assertOk()
        ->assertExactJson(['message' => 'Producto eliminado correctamente.']);

    expect(Product::query()->find($product->id))->toBeNull()
        ->and(ProductPrice::query()->where('product_id', $product->id)->count())->toBe(0);
});

it('returns 404 when deleting a product that does not exist', function () {
    $this->deleteJson('/api/products/99999')
        ->assertNotFound()
        ->assertJsonPath('error', 'NOT_FOUND')
        ->assertJsonPath('message', 'Producto no encontrado.');
});
