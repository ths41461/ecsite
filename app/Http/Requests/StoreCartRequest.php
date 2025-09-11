<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint for now (no auth in this phase)
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', 'min:1'],
            'qty'        => ['required', 'integer', 'min:1', 'max:' . (int)config('cart.max_qty', 20)],
        ];
    }
}
