<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint for now (no auth in this phase)
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', 'min:1', Rule::exists('product_variants', 'id')->where('is_active', 1)],
            'qty'        => ['required', 'integer', 'min:1', 'max:' . (int)config('cart.max_qty', 20)],
        ];
    }

    public function messages(): array
    {
        return [
            'variant_id.required' => 'Please choose a variant.',
            'variant_id.integer'  => 'Invalid variant.',
            'variant_id.min'      => 'Invalid variant.',
            'variant_id.exists'   => 'Selected variant is unavailable.',
            'qty.required'        => 'Please choose a quantity.',
            'qty.integer'         => 'Quantity must be a number.',
            'qty.min'             => 'Quantity must be at least 1.',
            'qty.max'             => 'Quantity may not exceed ' . (int)config('cart.max_qty', 20) . '.',
        ];
    }
}
