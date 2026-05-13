<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\SitemapController;
use App\Models\Form;
use App\Models\FormField;
use App\Services\CaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * ماژول فرم‌ساز: فرم‌ها توسط سایت مشتری ساخته و مدیریت می‌شوند.
 */
class FormController extends Controller
{
    public function index()
    {
        if (! Schema::hasTable('forms')) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'جدول فرم‌ها وجود ندارد. لطفاً دستور php artisan migrate را اجرا کنید.');
        }
        $forms = Form::query()
            ->withCount('submissions')
            ->withCount(['fields' => fn ($q) => $q->where('is_active', true)])
            ->latest()
            ->get();

        return view('admin.forms.index', compact('forms'));
    }

    public function create()
    {
        if (! Schema::hasTable('forms')) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'جدول فرم‌ها وجود ندارد. لطفاً دستور php artisan migrate را اجرا کنید.');
        }

        return view('admin.forms.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->formSettingsRules());

        $slug = $this->resolveSlugForSave(trim((string) ($data['slug'] ?? '')), $data['title'], null);
        if ($slug instanceof \Illuminate\Http\RedirectResponse) {
            return $slug;
        }

        $form = Form::create([
            'title' => $data['title'],
            'slug' => $slug,
            'is_active' => $request->boolean('is_active'),
            'meta_title' => $data['meta_title'] ?: null,
            'meta_description' => $data['meta_description'] ?: null,
            'canonical_url' => $data['canonical_url'] ?: null,
            'meta_robots' => $data['meta_robots'] ?: null,
            'intro_text' => $data['intro_text'] ?: null,
        ]);

        SitemapController::clearCache();

        return redirect()->route('admin.forms.edit', $form)->with('success', 'فرم ساخته شد؛ اکنون فیلدها را اضافه کنید.');
    }

    public function edit(Form $form)
    {
        $form->load('fields');

        return view('admin.forms.edit', compact('form'));
    }

    public function update(Request $request, Form $form)
    {
        $data = $request->validate($this->formSettingsRules());

        $slug = $this->resolveSlugForSave(trim((string) ($data['slug'] ?? '')), $data['title'], $form->id);
        if ($slug instanceof \Illuminate\Http\RedirectResponse) {
            return $slug;
        }

        $form->update([
            'title' => $data['title'],
            'slug' => $slug,
            'is_active' => $request->boolean('is_active'),
            'meta_title' => $data['meta_title'] ?: null,
            'meta_description' => $data['meta_description'] ?: null,
            'canonical_url' => $data['canonical_url'] ?: null,
            'meta_robots' => $data['meta_robots'] ?: null,
            'intro_text' => $data['intro_text'] ?: null,
        ]);

        SitemapController::clearCache();

        return back()->with('success', 'تنظیمات فرم ذخیره شد.');
    }

    public function destroy(Form $form)
    {
        $form->fields()->delete();
        $form->submissions()->delete();
        $form->delete();
        SitemapController::clearCache();

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
            'type' => 'required|string|in:text,textarea,email,number,date,radio,checkbox,select',
            'is_required' => 'nullable|boolean',
            'placeholder' => 'nullable|string|max:255',
            'help_text' => 'nullable|string|max:500',
            'options_raw' => 'nullable|string|max:8000',
        ]);

        $data['is_required'] = $request->has('is_required');
        $data['is_active'] = true;
        $data['position'] = $form->fields()->count() + 1;
        $data['placeholder'] = $data['placeholder'] ?: null;
        $data['help_text'] = $data['help_text'] ?: null;

        $options = null;
        if (in_array($data['type'], ['select', 'radio', 'checkbox'], true)) {
            $lines = preg_split('/\r\n|\r|\n/', (string) ($data['options_raw'] ?? '')) ?: [];
            $options = array_values(array_filter(array_map('trim', $lines), fn ($p) => $p !== ''));
            if (count($options) < 1) {
                return back()->withErrors(['options_raw' => 'برای این نوع فیلد حداقل یک گزینه وارد کنید (هر خط یک گزینه).'])->withInput();
            }
        }
        $data['options'] = $options;
        unset($data['options_raw']);

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
        $field->update(['is_active' => ! $field->is_active]);

        return back();
    }

    public function sortFields(Request $request, Form $form)
    {
        foreach ($request->positions as $position => $id) {
            FormField::where('id', $id)->update(['position' => $position]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * @return array<string, string|null>
     */
    private function formSettingsRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|string|max:500',
            'meta_robots' => 'nullable|string|max:80',
            // Wave 7.0b: intro_text is edited via TinyMCE; HTML markup pushes
            // the realistic payload well past the legacy 5,000-char ceiling.
            // 65,000 aligns with the underlying MySQL TEXT column (~64 KB)
            // declared in migration 2026_05_09_140000_add_seo_to_forms_table.
            'intro_text' => 'nullable|string|max:65000',
        ];
    }

    /**
     * @return string|\Illuminate\Http\RedirectResponse
     */
    private function resolveSlugForSave(string $slugInput, string $title, ?int $ignoreFormId)
    {
        if ($slugInput !== '') {
            if (! SlugHelper::isValid($slugInput)) {
                return back()->withErrors(['slug' => 'نامک فقط می‌تواند شامل حروف، اعداد و خط تیره باشد.'])->withInput();
            }

            return SlugHelper::unique(
                $slugInput,
                fn (string $s) => Form::query()
                    ->when($ignoreFormId, fn ($q) => $q->whereKeyNot($ignoreFormId))
                    ->where('slug', $s)
                    ->exists()
            );
        }

        return SlugHelper::unique(
            SlugHelper::make($title),
            fn (string $s) => Form::query()
                ->when($ignoreFormId, fn ($q) => $q->whereKeyNot($ignoreFormId))
                ->where('slug', $s)
                ->exists()
        );
    }
}
