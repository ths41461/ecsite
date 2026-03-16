<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_id.required' => 'セッションIDが必要です',
            'session_id.string' => 'セッションIDは文字列で入力してください',
            'session_id.max' => 'セッションIDが長すぎます',
            'message.required' => 'メッセージを入力してください',
            'message.string' => 'メッセージは文字列で入力してください',
            'message.max' => 'メッセージは1000文字以内で入力してください',
        ];
    }
}
