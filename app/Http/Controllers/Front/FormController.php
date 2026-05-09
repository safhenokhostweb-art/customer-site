<?php

namespace App\Http\Controllers\Front;

use App\Helpers\Jalali;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\CaptchaService;
use Illuminate\Http\Request;
class FormController extends Controller
{
    public function index()
    {
        $forms = Form::query()->where('is_active', true)->orderBy('title')->get();

        return view('forms.index', compact('forms'));
    }

    public function show(string $slug)
    {
        $form = Form::query()->where('slug', $slug)
            ->where('is_active', true)
            ->with(['fields' => fn ($q) => $q->where('is_active', true)->orderBy('position')])
            ->firstOrFail();

        $captcha = ['enabled' => false, 'type' => null, 'hcaptcha_site_key' => '', 'persian_code' => '', 'normal_text' => ''];
        if (CaptchaService::isModuleEnabled()) {
            $cfg = CaptchaService::getForSite(null);
            if ($cfg['enabled']) {
                $captcha['enabled'] = true;
                $captcha['type'] = $cfg['type'];
                $captcha['hcaptcha_site_key'] = $cfg['hcaptcha_site_key'];
                if ($cfg['type'] === CaptchaService::TYPE_PERSIAN_NUMBER) {
                    $captcha['persian_code'] = CaptchaService::generatePersianNumber(4);
                } elseif ($cfg['type'] === CaptchaService::TYPE_NORMAL) {
                    $gen = CaptchaService::generateNormal();
                    $captcha['normal_text'] = $gen['text'];
                }
            }
        }

        return view('forms.show', compact('form', 'captcha'));
    }

    public function submit(Request $request, string $slug)
    {
        $form = Form::query()->where('slug', $slug)
            ->where('is_active', true)
            ->with('fields')
            ->firstOrFail();

        $rules = [];
        foreach ($form->fields as $field) {
            $key = 'field_'.$field->id;
            if ($field->type === 'checkbox') {
                $rules[$key] = $field->is_required ? 'required|array|min:1' : 'nullable|array';
            } else {
                $rules[$key] = $field->is_required ? 'required' : 'nullable';
            }
        }

        $captchaValid = true;
        if (CaptchaService::isModuleEnabled()) {
            $cfg = CaptchaService::getForSite(null);
            if ($cfg['enabled']) {
                if ($cfg['type'] === CaptchaService::TYPE_PERSIAN_NUMBER) {
                    $captchaValid = CaptchaService::validatePersianNumber($request->input('captcha_answer', ''));
                } elseif ($cfg['type'] === CaptchaService::TYPE_NORMAL) {
                    $captchaValid = CaptchaService::validateNormal($request->input('captcha_answer', ''));
                } elseif ($cfg['type'] === CaptchaService::TYPE_HCAPTCHA) {
                    $captchaValid = CaptchaService::verifyHcaptcha($request->input('h-captcha-response', ''), $cfg['hcaptcha_secret_key']);
                }
                if (! $captchaValid) {
                    return back()->withInput()->withErrors(['captcha' => 'کپچا اشتباه است یا منقضی شده. لطفا دوباره تلاش کنید.']);
                }
            }
        }

        $validated = $request->validate($rules);

        $payload = [];
        foreach ($form->fields as $field) {
            $key = 'field_'.$field->id;
            if (! array_key_exists($key, $validated)) {
                continue;
            }
            $value = $validated[$key];
            if ($field->type === 'date' && $value !== null && $value !== '') {
                try {
                    $value = Jalali::toCarbon((string) $value)->format('Y-m-d');
                } catch (\Throwable $e) {
                    // نگه‌داشت مقدار خام در صورت خطای نادر پارس
                }
            }
            if ($field->type === 'checkbox' && is_array($value)) {
                $value = implode('، ', array_map('strval', $value));
            }
            $payload[$key] = $value;
        }

        FormSubmission::create([
            'form_id' => $form->id,
            'data' => $payload,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'فرم با موفقیت ارسال شد ✅');
    }
}
