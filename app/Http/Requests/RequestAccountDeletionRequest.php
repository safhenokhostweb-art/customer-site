<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestAccountDeletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'current_password'],
        ];
    }

    public function attributes(): array
    {
        return [
            'reason' => 'دلیل',
            'password' => 'رمز عبور',
        ];
    }
}
