<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'tax_cost' => $this->tax_cost,
            'manufacturing_cost' => $this->manufacturing_cost,
            'currency' => new CurrencyResource($this->currency),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($this->price_list) {
            $resource['price_list'] = $this->price_list;
            unset($resource['currency']);
            unset($resource['created_at']);
            unset($resource['updated_at']);
        }

        return $resource;
    }
}
