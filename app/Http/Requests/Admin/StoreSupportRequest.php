<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
            'priority' => 'nullable|in:low,normal,high',
            'category' => 'nullable|string|max:50',
            'tags' => 'nullable|string|max:500',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,zip|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'عنوان تیکت الزامی است.',
            'message.required' => 'متن تیکت الزامی است.',
            'email.required' => 'ایمیل الزامی است.',
            'email.email' => 'ایمیل وارد شده معتبر نیست.',
            'priority.in' => 'اولویت انتخاب شده معتبر نیست.',
            'attachments.max' => 'حداکثر ۵ فایل قابل ارسال است.',
            'attachments.*.mimes' => 'فرمت فایل باید jpg, jpeg, png, pdf, doc, docx, zip باشد.',
            'attachments.*.max' => 'حداکثر حجم هر فایل ۱۰ مگابایت است.',
        ];
    }
}
