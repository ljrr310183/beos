<?php

namespace App\Http\Requests;

use App\Models\ProductPrice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StorePriceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The payload is a bare list of prices. Item rules are derived from
     * ProductPrice::$fillable so they cannot drift from the persisted columns.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $itemRules = [
            'product_id' => [
                'required',
                'integer',
                Rule::in([$this->productId()]),
            ],
            'currency_id' => [
                'required',
                'integer',
                'exists:currencies,id',
                'distinct',
                Rule::unique('product_prices', 'currency_id')
                    ->where('product_id', $this->productId()),
            ],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.9999', 'decimal:0,4'],
        ];

        $rules = [
            '*' => ['required', 'array'],
        ];

        foreach (Arr::only($itemRules, (new ProductPrice)->getFillable()) as $attribute => $attributeRules) {
            $rules["*.{$attribute}"] = $attributeRules;
        }

        return $rules;
    }

    /**
     * Reject a non-list payload, an empty list, and any attribute outside
     * ProductPrice::$fillable.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $payload = $this->all();

            if (! array_is_list($payload)) {
                $validator->errors()->add('payload', 'El payload debe ser un arreglo de precios.');

                return;
            }

            if ($payload === []) {
                $validator->errors()->add('payload', 'El listado de precios debe contener al menos un elemento.');

                return;
            }

            // Same ceiling as the nested `prices` array in StoreProductRequest (max:50).
            if (count($payload) > 50) {
                $validator->errors()->add('payload', 'El listado de precios no puede superar los 50 elementos.');

                return;
            }

            foreach ($payload as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $unexpected = array_diff(array_keys($item), (new ProductPrice)->getFillable());

                if ($unexpected !== []) {
                    $validator->errors()->add(
                        "{$index}.payload",
                        'El payload contiene atributos no soportados: '.implode(', ', $unexpected).'.',
                    );
                }
            }
        });
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.required' => 'Cada elemento del listado debe ser un objeto.',
            '*.array' => 'Cada elemento del listado debe ser un objeto.',

            '*.product_id.required' => 'El producto es obligatorio en cada elemento.',
            '*.product_id.integer' => 'El producto debe ser un número entero.',
            '*.product_id.in' => 'El producto del payload no coincide con el producto de la ruta.',

            '*.currency_id.required' => 'La divisa es obligatoria.',
            '*.currency_id.integer' => 'La divisa debe ser un número entero.',
            '*.currency_id.exists' => 'La divisa seleccionada no existe.',
            '*.currency_id.distinct' => 'El listado contiene divisas repetidas.',
            '*.currency_id.unique' => 'El precio para esta divisa ya ha sido registrado para este producto.',

            '*.price.required' => 'El precio es obligatorio.',
            '*.price.numeric' => 'El precio debe ser un número.',
            '*.price.min' => 'El precio debe ser al menos 0.',
            '*.price.max' => 'El precio no debe ser mayor que 99999999.9999.',
            '*.price.decimal' => 'El precio debe tener entre 0 y 4 decimales.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            '*.product_id' => 'producto',
            '*.currency_id' => 'divisa',
            '*.price' => 'precio',
        ];
    }

    private function productId(): int
    {
        return (int) $this->route('id');
    }
}
