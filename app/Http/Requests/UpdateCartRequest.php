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
            'qty.required' => '数量を選択してください。',
            'qty.integer'  => '数量は数字で入力してください。',
            'qty.min'      => '数量は負の数にできません。',
            'qty.max'      => '数量は ' . (int)config('cart.max_qty', 20) . ' を超えることはできません。',
        ];
    }
}
