@extends('admin.layout')
@section('title', 'ویرایش فرم: ' . $form->title)
@section('header', 'ویرایش فرم: ' . $form->title)
@section('content')

@if(session('success'))
    <div class="bg-emerald-50 text-emerald-900 border border-emerald-200 p-3 rounded-lg mb-4">{{ session('success') }}</div>
@endif

<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">ویرایش فرم</h1>
            <p class="admin-section-lead" style="margin-top:0.35rem">{{ $form->title }}</p>
        </div>
        <div class="admin-page-tools flex flex-wrap gap-2">
            <a href="{{ route('admin.forms.preview', $form) }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">پیش‌نمایش</a>
            <a href="{{ route('forms.show', $form->slug) }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">مشاهده در سایت</a>
            <a href="{{ route('admin.forms.index') }}" class="btn btn-outline-secondary btn-sm">فهرست فرم‌ها</a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header">تنظیمات فرم، سئو و نامک</div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="bg-red-50 text-red-800 border border-red-200 p-3 rounded mb-4 text-sm">
                        <ul class="list-disc pr-5 m-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.forms.update', $form) }}" class="admin-form-stack">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="form-label" for="form-title">عنوان فرم</label>
                        <input id="form-title" type="text" name="title" class="w-full border rounded-lg px-3 py-2" value="{{ old('title', $form->title) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="form-slug">نامک URL</label>
                        <input id="form-slug" type="text" name="slug" class="w-full border rounded-lg px-3 py-2 font-mono text-sm" dir="ltr" value="{{ old('slug', $form->slug) }}">
                        <span class="admin-field-hint">با تغییر نامک، آدرس عمومی فرم عوض می‌شود؛ لینک‌های قدیمی دیگر کار نمی‌کنند.</span>
                    </div>
                    <label class="admin-form-inline-check mb-3">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $form->is_active) ? 'checked' : '' }}>
                        فعال در سایت
                    </label>

                    <div class="form-group">
                        <label class="form-label" for="form-intro">متن بالای فرم</label>
                        <textarea id="form-intro" name="intro_text" rows="3" class="w-full border rounded-lg px-3 py-2">{{ old('intro_text', $form->intro_text) }}</textarea>
                    </div>

                    <p class="text-sm font-semibold text-slate-700 mb-2">سئو</p>
                    <div class="form-group">
                        <label class="form-label" for="form-meta-title">عنوان متا</label>
                        <input id="form-meta-title" type="text" name="meta_title" class="w-full border rounded-lg px-3 py-2" value="{{ old('meta_title', $form->meta_title) }}" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="form-meta-desc">توضیح متا</label>
                        <textarea id="form-meta-desc" name="meta_description" rows="2" class="w-full border rounded-lg px-3 py-2" maxlength="500">{{ old('meta_description', $form->meta_description) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="form-canonical">Canonical</label>
                        <input id="form-canonical" type="text" name="canonical_url" class="w-full border rounded-lg px-3 py-2 font-mono text-sm" dir="ltr" value="{{ old('canonical_url', $form->canonical_url) }}" maxlength="500">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="form-robots">Robots</label>
                        <input id="form-robots" type="text" name="meta_robots" class="w-full border rounded-lg px-3 py-2 font-mono text-sm" dir="ltr" value="{{ old('meta_robots', $form->meta_robots) }}" maxlength="80" placeholder="index,follow">
                    </div>

                    <button type="submit" class="btn btn-primary">ذخیره تنظیمات</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">افزودن فیلد</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.forms.fields.store', $form) }}" class="admin-form-stack" id="form-add-field">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">عنوان فیلد</label>
                        <input name="label" class="w-full border rounded-lg px-3 py-2" placeholder="مثال: نام" required value="{{ old('label') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">نام داخلی (انگلیسی)</label>
                        <input name="name" class="w-full border rounded-lg px-3 py-2 font-mono text-sm" dir="ltr" placeholder="full_name" required value="{{ old('name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">نوع</label>
                        <select name="type" class="w-full border rounded-lg px-3 py-2" id="new-field-type">
                            <option value="text">متن یک خط</option>
                            <option value="textarea">متن چند خط</option>
                            <option value="email">ایمیل</option>
                            <option value="number">عدد</option>
                            <option value="date">تاریخ (شمسی)</option>
                            <option value="select">لیست کشویی</option>
                            <option value="radio">رادیو</option>
                            <option value="checkbox">چک‌باکس چندگانه</option>
                        </select>
                    </div>
                    <div class="form-group" id="wrap-options">
                        <label class="form-label">گزینه‌ها (هر خط یک مورد)</label>
                        <textarea name="options_raw" class="w-full border rounded-lg px-3 py-2 font-mono text-sm" rows="4" dir="ltr" placeholder="گزینه ۱&#10;گزینه ۲">{{ old('options_raw') }}</textarea>
                        <span class="admin-field-hint">برای لیست، رادیو و چک‌باکس الزامی است.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Placeholder</label>
                        <input name="placeholder" class="w-full border rounded-lg px-3 py-2" value="{{ old('placeholder') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">راهنمای کوتاک زیر فیلد</label>
                        <input name="help_text" class="w-full border rounded-lg px-3 py-2" value="{{ old('help_text') }}">
                    </div>
                    <label class="admin-form-inline-check">
                        <input type="checkbox" name="is_required" value="1" {{ old('is_required') ? 'checked' : '' }}>
                        الزامی
                    </label>
                    <button type="submit" class="btn btn-primary mt-2">افزودن فیلد</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card mt-6">
        <div class="card-header">فیلدهای فعلی ({{ $form->fields->count() }})</div>
        <div class="card-body p-0">
            @if($errors->has('options_raw'))
                <div class="p-4 text-sm text-red-700 bg-red-50 border-b border-red-100">{{ $errors->first('options_raw') }}</div>
            @endif
            <ul class="divide-y divide-slate-100">
                @foreach($form->fields()->orderBy('position')->get() as $field)
                    <li class="flex flex-wrap justify-between items-center gap-3 px-4 py-3 hover:bg-slate-50">
                        <div>
                            <span class="font-medium">{{ $field->label }}</span>
                            <span class="text-xs text-slate-500 mr-2">({{ $field->type }})</span>
                            @if(!$field->is_active)
                                <span class="badge badge-draft text-xs">غیرفعال</span>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('admin.forms.fields.toggle', $field) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-secondary btn-sm">{{ $field->is_active ? 'غیرفعال' : 'فعال' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.forms.fields.destroy', $field) }}" class="inline" onsubmit="return confirm('حذف فیلد؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if($form->fields->isEmpty())
                <p class="p-6 text-slate-500 text-sm m-0">هنوز فیلدی اضافه نشده است.</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('new-field-type');
    var wrap = document.getElementById('wrap-options');
    function sync() {
        if (!sel || !wrap) return;
        var t = sel.value;
        var needs = ['select','radio','checkbox'].indexOf(t) !== -1;
        wrap.style.display = needs ? 'block' : 'none';
    }
    if (sel) { sel.addEventListener('change', sync); sync(); }
});
</script>
@endpush
@endsection
