<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'برچسب',
            'address_line1' => 'آدرس',
            'address_line2' => 'آدرس تکمیلی',
            'city' => 'شهر',
            'state' => 'استان',
            'postal_code' => 'کد پستی',
            'country' => 'کشور',
        ];
    }
}
