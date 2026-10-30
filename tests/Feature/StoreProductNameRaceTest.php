<?php

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDOException;

uses(RefreshDatabase::class);

afterEach(function () {
    Product::flushEventListeners();
    Product::clearBootedModels();
});

it('reports a duplicate product name raised by the database as a validation error', function () {
    $currency = Currency::factory()->usd()->create();

    Product::creating(function (Product $product): void {
        DB::table('products')->insert([
            'name' => $product->name,
            'price' => '1.0000',
            'currency_id' => $product->currency_id,
            'tax_cost' => '0.0000',
            'manufacturing_cost' => '0.5000',
        ]);
    });

    $this->postJson('/api/products', [
        'name' => 'Race condition item',
        'description' => 'Product created twice in parallel.',
        'price' => 100,
        'currency_id' => $currency->id,
        'tax_cost' => 16,
        'manufacturing_cost' => 40,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error', 'VALIDATION_ERROR')
        ->assertJsonValidationErrors('name');

    expect(Product::query()->count())->toBe(0);
});

it('rethrows a database failure that is not a duplicate key', function () {
    $currency = Currency::factory()->usd()->create();

    DB::beforeExecuting(function (string $query): void {
        if (str_starts_with($query, 'insert into "products"')) {
            throw new QueryException('sqlite', $query, [], new PDOException('disk I/O error'));
        }
    });

    $this->postJson('/api/products', [
        'name' => 'Broken insert',
        'description' => 'Insert that fails for a reason other than a duplicate key.',
        'price' => 100,
        'currency_id' => $currency->id,
        'tax_cost' => 16,
        'manufacturing_cost' => 40,
    ])->assertStatus(500);

    expect(Product::query()->count())->toBe(0);
});
