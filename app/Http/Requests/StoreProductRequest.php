<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:products,name'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.9999', 'decimal:0,4'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'tax_cost' => ['required', 'numeric', 'min:0', 'max:99999999.9999', 'decimal:0,4'],
            'manufacturing_cost' => ['required', 'numeric', 'min:0', 'max:99999999.9999', 'decimal:0,4'],

            'prices' => ['sometimes', 'array', 'max:50'],
            'prices.*' => ['array'],
            'prices.*.currency_id' => ['required', 'integer', 'exists:currencies,id', 'distinct'],
            'prices.*.price' => ['required', 'numeric', 'min:0', 'max:9999999999.9999', 'decimal:0,4'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del producto es obligatorio.',
            'name.string' => 'El nombre del producto debe ser una cadena de texto.',
            'name.max' => 'El nombre del producto no debe tener más de 255 caracteres.',
            'name.unique' => 'El nombre del producto ya ha sido registrado.',

            'description.required' => 'La descripción es obligatoria.',
            'description.string' => 'La descripción debe ser una cadena de texto.',

            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio debe ser al menos 0.',
            'price.max' => 'El precio no debe ser mayor que 99999999.9999.',
            'price.decimal' => 'El precio debe tener entre 0 y 4 decimales.',

            'currency_id.required' => 'La divisa es obligatoria.',
            'currency_id.integer' => 'La divisa debe ser un número entero.',
            'currency_id.exists' => 'La divisa seleccionada no existe.',

            'tax_cost.required' => 'El costo de impuesto es obligatorio.',
            'tax_cost.numeric' => 'El costo de impuesto debe ser un número.',
            'tax_cost.min' => 'El costo de impuesto debe ser al menos 0.',
            'tax_cost.max' => 'El costo de impuesto no debe ser mayor que 99999999.9999.',
            'tax_cost.decimal' => 'El costo de impuesto debe tener entre 0 y 4 decimales.',

            'manufacturing_cost.required' => 'El costo de fabricación es obligatorio.',
            'manufacturing_cost.numeric' => 'El costo de fabricación debe ser un número.',
            'manufacturing_cost.min' => 'El costo de fabricación debe ser al menos 0.',
            'manufacturing_cost.max' => 'El costo de fabricación no debe ser mayor que 99999999.9999.',
            'manufacturing_cost.decimal' => 'El costo de fabricación debe tener entre 0 y 4 decimales.',

            'prices.array' => 'Los precios deben ser un arreglo.',
            'prices.max' => 'El listado de precios no debe contener más de 50 elementos.',
            'prices.*.array' => 'Cada precio debe ser un objeto.',
            'prices.*.currency_id.required' => 'Cada precio requiere una divisa.',
            'prices.*.currency_id.integer' => 'La divisa de cada precio debe ser un número entero.',
            'prices.*.currency_id.exists' => 'La divisa seleccionada no existe.',
            'prices.*.currency_id.distinct' => 'Cada divisa solo puede aparecer una vez en los precios.',
            'prices.*.price.required' => 'Cada precio requiere un monto.',
            'prices.*.price.numeric' => 'El monto de cada precio debe ser un número.',
            'prices.*.price.min' => 'El monto de cada precio debe ser al menos 0.',
            'prices.*.price.max' => 'El monto de cada precio no debe ser mayor que 9999999999.9999.',
            'prices.*.price.decimal' => 'El monto de cada precio debe tener entre 0 y 4 decimales.',
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
            'name' => 'nombre del producto',
            'description' => 'descripción',
            'price' => 'precio',
            'currency_id' => 'divisa',
            'tax_cost' => 'costo de impuesto',
            'manufacturing_cost' => 'costo de fabricación',
            'prices' => 'precios',
            'prices.*.currency_id' => 'divisa del precio',
            'prices.*.price' => 'monto del precio',
        ];
    }
}
