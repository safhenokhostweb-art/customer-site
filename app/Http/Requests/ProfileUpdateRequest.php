<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'mobile' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:1000'],
            'birth_date' => ['nullable', 'string', 'max:20', function ($attr, $value, $fail) {
                if ($value && !\App\Helpers\Jalali::toCarbon($value)) {
                    $fail('فرمت تاریخ تولد نامعتبر است. مثال: ۱۴۰۳/۰۱/۰۱');
                }
            }],
            'phone' => ['nullable', 'string', 'max:30'],
            'fax' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'string', 'max:255', function ($attr, $value, $fail) {
                if ($value) {
                    $url = preg_match('#^https?://#', $value) ? $value : 'https://' . $value;
                    if (!filter_var($url, FILTER_VALIDATE_URL)) {
                        $fail('آدرس وب‌سایت نامعتبر است.');
                    }
                }
            }],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_role' => ['nullable', 'string', 'max:100'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_phone' => ['nullable', 'string', 'max:30'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'notify_email' => ['nullable', 'boolean'],
            'notify_sms' => ['nullable', 'boolean'],
            'marketing' => ['nullable', 'boolean'],
            'show_email' => ['nullable', 'boolean'],
            'show_phone' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'نام',
            'email' => 'ایمیل',
            'mobile' => 'موبایل',
            'bio' => 'درباره من',
            'birth_date' => 'تاریخ تولد',
            'phone' => 'تلفن',
            'website' => 'وب‌سایت',
            'company_name' => 'نام شرکت',
            'company_role' => 'سمت',
        ];
    }
}
