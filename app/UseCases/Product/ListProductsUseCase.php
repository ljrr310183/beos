<?php

namespace App\UseCases\Product;

use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ListProductsUseCase
{
    public function execute(Request $request): LengthAwarePaginator
    {
        return ProductRepository::getAll($request);
    }
}
