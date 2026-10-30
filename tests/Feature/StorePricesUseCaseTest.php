<?php

use App\Http\Requests\StorePriceRequest;
use App\Models\Currency;
use App\Models\Product;
use App\UseCases\Product\StorePricesUseCase;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDOException;

uses(RefreshDatabase::class);

it('rethrows a database failure that is not a duplicate key', function () {
    $product = Product::factory()->create();
    $eur = Currency::factory()->eur()->create();

    $request = $this->mock(StorePriceRequest::class);
    $request->shouldReceive('validated')->once()->andReturn([
        ['product_id' => $product->id, 'currency_id' => $eur->id, 'price' => '10.0000'],
    ]);

    DB::beforeExecuting(function (): void {
        throw new QueryException('sqlite', 'insert into "product_prices" values (?)', [], new PDOException('disk I/O error'));
    });

    expect(fn () => (new StorePricesUseCase)->execute($product, $request))
        ->toThrow(QueryException::class);
});
