@extends('admin.layout')
@section('title', $gate ? 'ویرایش گیت دسترسی' : 'گیت دسترسی جدید')

@section('content')
<div class="admin-page-container max-w-2xl">

    <div class="admin-page-header flex items-center gap-3 mb-6">
        <a href="{{ route('admin.access-lists.gates.index') }}" class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition">
            <x-core-ui::icon name="arrow-right" class="w-5 h-5" />
        </a>
        <div>
            <h1 class="admin-page-title">{{ $gate ? 'ویرایش گیت دسترسی' : 'گیت دسترسی جدید' }}</h1>
        </div>
    </div>

    <div class="admin-card">
        <form method="POST"
              action="{{ $gate ? route('admin.access-lists.gates.update', $gate) : route('admin.access-lists.gates.store') }}"
              class="p-6 space-y-5">
            @csrf
            @if($gate) @method('PUT') @endif

            @if($errors->any())
            <div class="alert alert-danger">
                <ul class="list-disc list-inside"><li>{{ $errors->first() }}</li></ul>
            </div>
            @endif

            <div class="form-group">
                <label class="form-label">لیست دسترسی <span class="text-red-500">*</span></label>
                <select name="access_list_id" class="form-input" required>
                    <option value="">انتخاب کنید...</option>
                    @foreach($lists as $l)
                    <option value="{{ $l->id }}" {{ old('access_list_id', $gate?->access_list_id) == $l->id ? 'selected' : '' }}>
                        {{ $l->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">نام گیت <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $gate?->name) }}"
                       class="form-input" placeholder="مثال: دسترسی آزمون مرحله اول" required>
            </div>

            <div class="form-group">
                <label class="form-label">Slug (شناسه URL) <span class="text-red-500">*</span></label>
                <div style="display:flex;align-items:center;border:1.5px solid #e2e8f0;border-radius:.625rem;overflow:hidden">
                    <span style="padding:.625rem .875rem;background:#f8fafc;color:#64748b;font-size:.8rem;border-left:1.5px solid #e2e8f0">/gate/</span>
                    <input type="text" name="slug" value="{{ old('slug', $gate?->slug) }}"
                           style="flex:1;border:none;outline:none;padding:.625rem .875rem;font-family:monospace;font-size:.875rem"
                           placeholder="exam-phase-1" required>
                </div>
                <p class="text-xs text-slate-400 mt-1">فقط حروف انگلیسی، اعداد و خط تیره</p>
            </div>

            <div class="form-group">
                <label class="form-label">توضیح (نمایش روی فرم احراز هویت)</label>
                <textarea name="description" rows="2" class="form-input"
                          placeholder="برای دسترسی به این بخش، کد ملی خود را وارد کنید...">{{ old('description', $gate?->description) }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">آدرس مقصد پس از تایید <span class="text-red-500">*</span></label>
                <input type="url" name="redirect_url" value="{{ old('redirect_url', $gate?->redirect_url) }}"
                       class="form-input" placeholder="https://..." required>
            </div>

            <div class="form-group">
                <label class="form-label">مدت اعتبار جلسه (دقیقه) <span class="text-red-500">*</span></label>
                <input type="number" name="session_duration"
                       value="{{ old('session_duration', $gate?->session_duration ?? 1440) }}"
                       class="form-input" min="5" max="43200" required>
                <p class="text-xs text-slate-400 mt-1">پیش‌فرض ۱۴۴۰ دقیقه (۲۴ ساعت) — پس از این مدت احراز هویت مجدد نیاز است</p>
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:.625rem;cursor:pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $gate?->is_active ?? true) ? 'checked' : '' }}
                           style="width:1rem;height:1rem;accent-color:#4f46e5">
                    <span class="form-label" style="margin:0">گیت فعال باشد</span>
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary">
                    <x-core-ui::icon name="check" class="w-4 h-4 ml-1" />
                    {{ $gate ? 'ذخیره تغییرات' : 'ایجاد گیت' }}
                </button>
                <a href="{{ route('admin.access-lists.gates.index') }}" class="btn btn-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Auto-generate slug from name
(function(){
    var nameInput = document.querySelector('input[name=name]');
    var slugInput = document.querySelector('input[name=slug]');
    if (!nameInput || !slugInput || slugInput.value) return;
    nameInput.addEventListener('input', function(){
        if (slugInput._touched) return;
        slugInput.value = this.value
            .toLowerCase()
            .replace(/[\s_]+/g, '-')
            .replace(/[^a-z0-9-]/g, '')
            .replace(/-+/g, '-');
    });
    slugInput.addEventListener('input', function(){ slugInput._touched = true; });
})();
</script>
@endpush
@endsection
