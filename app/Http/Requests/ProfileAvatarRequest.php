<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpeg,png,gif,webp', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'avatar' => 'تصویر پروفایل',
        ];
    }
}
