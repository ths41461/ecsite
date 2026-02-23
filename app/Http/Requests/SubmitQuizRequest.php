<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'personality' => ['required', 'string', 'in:romantic,energetic,cool,natural'],
            'vibe' => ['required', 'string', 'in:floral,citrus,vanilla,woody,ocean'],
            'occasion' => ['required', 'array', 'min:1'],
            'occasion.*' => ['required', 'string', 'in:daily,date,special,work,casual'],
            'style' => ['required', 'string', 'in:feminine,casual,chic,natural'],
            'budget' => ['required', 'integer', 'min:1000', 'max:50000'],
            'experience' => ['required', 'string', 'in:beginner,intermediate,advanced'],
            'season' => ['nullable', 'string', 'in:spring_summer,fall_winter,all_year'],
        ];
    }

    public function messages(): array
    {
        return [
            'personality.required' => '性格を選択してください',
            'personality.in' => '選択された性格が無効です',
            'vibe.required' => '好みの香りを選択してください',
            'vibe.in' => '選択された香りが無効です',
            'occasion.required' => '使用シーンを選択してください',
            'occasion.min' => '使用シーンを少なくとも1つ選択してください',
            'occasion.*.in' => '選択された使用シーンが無効です',
            'style.required' => 'スタイルを選択してください',
            'style.in' => '選択されたスタイルが無効です',
            'budget.required' => '予算を設定してください',
            'budget.min' => '予算は1,000円以上で設定してください',
            'budget.max' => '予算は50,000円以下で設定してください',
            'experience.required' => '香水経験を選択してください',
            'experience.in' => '選択された香水経験が無効です',
            'season.in' => '選択された季節が無効です',
        ];
    }
}
