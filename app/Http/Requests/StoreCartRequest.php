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
            'variant_id.required' => 'バリエーションを選択してください。',
            'variant_id.integer'  => '無効なバリエーションです。',
            'variant_id.min'      => '無効なバリエーションです。',
            'variant_id.exists'   => '選択されたバリエーションはご利用いただけません。',
            'qty.required'        => '数量を選択してください。',
            'qty.integer'         => '数量は数字で入力してください。',
            'qty.min'             => '数量は1以上で入力してください。',
            'qty.max'             => '数量は ' . (int)config('cart.max_qty', 20) . ' を超えることはできません。',
        ];
    }
}
