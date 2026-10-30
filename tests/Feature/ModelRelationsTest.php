<?php

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exposes the inverse relations of a currency and a product price', function () {
    $usd = Currency::factory()->usd()->create();
    $eur = Currency::factory()->eur()->create();
    $product = Product::factory()->forCurrency($usd->id)->create();
    $price = ProductPrice::factory()->forProduct($product)->forCurrency($eur)->priced(15.5)->create();

    expect($price->product->is($product))->toBeTrue()
        ->and($usd->products()->count())->toBe(1)
        ->and($usd->prices()->count())->toBe(0)
        ->and($eur->products()->count())->toBe(0)
        ->and($eur->prices()->count())->toBe(1);
});
