<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:10000'],
            'mentioned_user_ids' => ['sometimes', 'array'],
            'mentioned_user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
