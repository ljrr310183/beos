<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePriceRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductPriceResource;
use App\Http\Resources\ProductResource;
use App\UseCases\Product\DeleteProductUseCase;
use App\UseCases\Product\GetPricesPerProductUseCase;
use App\UseCases\Product\GetProductUseCase;
use App\UseCases\Product\ListProductsUseCase;
use App\UseCases\Product\StorePricesUseCase;
use App\UseCases\Product\StoreProductUseCase;
use App\UseCases\Product\UpdateProductUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ListProductsUseCase $listProductsUseCase,
        private readonly GetProductUseCase $getProductUseCase,
        private readonly GetPricesPerProductUseCase $getPricesPerProductUseCase,
        private readonly StoreProductUseCase $storeProductUseCase,
        private readonly UpdateProductUseCase $updateProductUseCase,
        private readonly DeleteProductUseCase $deleteProductUseCase,
        private readonly StorePricesUseCase $storePricesUseCase,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ProductResource::collection($this->listProductsUseCase->execute($request));
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $product = $this->storeProductUseCase->execute($request);

        return new ProductResource($product);
    }

    public function show(int $id): ProductResource
    {
        $product = $this->getProductUseCase->execute($id);
        abort_if(! $product, 404, 'Product not found');

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, int $id): ProductResource
    {
        $product = $this->getProductUseCase->execute($id);
        abort_if(! $product, 404, 'Product not found');

        $product = $this->updateProductUseCase->execute($product, $request);

        return new ProductResource($product);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = $this->getProductUseCase->execute($id);
        abort_if(! $product, 404, 'Product not found');

        $this->deleteProductUseCase->execute($product);

        return response()->json(['message' => 'Producto eliminado correctamente.']);
    }

    public function prices(int $id): ProductResource
    {
        $prices = $this->getPricesPerProductUseCase->execute($id);
        abort_if(! $prices, 404, 'Product not found');

        return new ProductResource($prices);
    }

    public function storePrices(StorePriceRequest $request, int $id): JsonResponse
    {
        $product = $this->getProductUseCase->execute($id);
        abort_if(! $product, 404, 'Product not found');

        $prices = $this->storePricesUseCase->execute($product, $request);

        return ProductPriceResource::collection($prices)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
