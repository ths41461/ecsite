<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint for now
    }

    public function rules(): array
    {
        return [
            'qty' => ['required', 'integer', 'min:0', 'max:' . (int)config('cart.max_qty', 20)],
        ];
    }

    public function messages(): array
    {
        return [
            'qty.required' => 'Please choose a quantity.',
            'qty.integer'  => 'Quantity must be a number.',
            'qty.min'      => 'Quantity cannot be negative.',
            'qty.max'      => 'Quantity may not exceed ' . (int)config('cart.max_qty', 20) . '.',
        ];
    }
}
