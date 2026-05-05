<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use App\Services\CaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * ماژول فرم‌ساز: فرم‌ها توسط سایت مشتری ساخته و مدیریت می‌شوند.
 */
class FormController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('forms')) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'جدول فرم‌ها وجود ندارد. لطفاً دستور php artisan migrate را اجرا کنید.');
        }
        $forms = Form::latest()->get();
        return view('admin.forms.index', compact('forms'));
    }

    public function create()
    {
        if (!Schema::hasTable('forms')) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'جدول فرم‌ها وجود ندارد. لطفاً دستور php artisan migrate را اجرا کنید.');
        }
        return view('admin.forms.create');
    }

    public function store(Request $request)
    {
        $request->validate(['title' => 'required|string|max:255']);

        Form::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . time(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.forms.index')->with('success', 'فرم ساخته شد');
    }

    public function edit(Form $form)
    {
        $form->load('fields');
        return view('admin.forms.edit', compact('form'));
    }

    public function update(Request $request, Form $form)
    {
        $form->update([
            'title' => $request->title,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'فرم ویرایش شد');
    }

    public function destroy(Form $form)
    {
        $form->fields()->delete();
        $form->submissions()->delete();
        $form->delete();

        return redirect()->route('admin.forms.index')->with('success', 'فرم حذف شد');
    }

    public function preview(Form $form)
    {
        $form->load(['fields' => fn ($q) => $q->where('is_active', true)->orderBy('position')]);
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
        return view('admin.forms.preview', compact('form', 'captcha'));
    }

    public function storeField(Request $request, Form $form)
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'is_required' => 'nullable|boolean',
        ]);

        $data['is_required'] = $request->has('is_required');
        $data['is_active'] = true;
        $data['position'] = $form->fields()->count() + 1;

        $form->fields()->create($data);

        return back()->with('success', 'فیلد با موفقیت اضافه شد');
    }

    public function destroyField(FormField $field)
    {
        $field->delete();
        return back()->with('success', 'فیلد حذف شد');
    }

    public function toggleField(FormField $field)
    {
        $field->update(['is_active' => !$field->is_active]);
        return back();
    }

    public function sortFields(Request $request, Form $form)
    {
        foreach ($request->positions as $position => $id) {
            FormField::where('id', $id)->update(['position' => $position]);
        }
        return response()->json(['status' => 'ok']);
    }
}
