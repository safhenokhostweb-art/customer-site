@extends('admin.layout')
@section('title', $list ? 'ویرایش لیست دسترسی' : 'لیست دسترسی جدید')

@section('content')
<div class="admin-page-container max-w-2xl">

    <div class="admin-page-header flex items-center gap-3 mb-6">
        <a href="{{ route('admin.access-lists.lists.index') }}" class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition">
            <x-core-ui::icon name="arrow-right" class="w-5 h-5" />
        </a>
        <div>
            <h1 class="admin-page-title">{{ $list ? 'ویرایش لیست' : 'لیست دسترسی جدید' }}</h1>
            @if($list)<p class="admin-page-subtitle text-sm text-slate-500 mt-0.5">{{ $list->name }}</p>@endif
        </div>
    </div>

    <div class="admin-card">
        <form method="POST"
              action="{{ $list ? route('admin.access-lists.lists.update', $list) : route('admin.access-lists.lists.store') }}"
              enctype="multipart/form-data"
              class="p-6 space-y-5">
            @csrf
            @if($list) @method('PUT') @endif

            @if($errors->any())
            <div class="alert alert-danger">
                <ul class="list-disc list-inside"><li>{{ $errors->first() }}</li></ul>
            </div>
            @endif

            <div class="form-group">
                <label class="form-label">نام لیست <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $list?->name) }}"
                       class="form-input" placeholder="مثال: دانش‌آموزان مدرسه X" required>
            </div>

            <div class="form-group">
                <label class="form-label">توضیح (اختیاری)</label>
                <textarea name="description" rows="2" class="form-input"
                          placeholder="کاربرد این لیست...">{{ old('description', $list?->description) }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">نوع تطابق <span class="text-red-500">*</span></label>
                <select name="match_type" class="form-input" required>
                    @foreach(['national_id' => 'کد ملی (فقط)', 'last_name' => 'نام خانوادگی (فقط)', 'both' => 'هر دو (کد ملی + نام خانوادگی)'] as $v => $l)
                    <option value="{{ $v }}" {{ old('match_type', $list?->match_type ?? 'national_id') === $v ? 'selected' : '' }}>
                        {{ $l }}
                    </option>
                    @endforeach
                </select>
            </div>

            @if(!$list)
            <div class="form-group">
                <label class="form-label">آپلود فایل اکسل (اختیاری)</label>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="form-input">
                <p class="text-xs text-slate-400 mt-1">سطر اول باید عنوان ستون‌ها باشد: «کد ملی» یا «نام خانوادگی» (فارسی یا انگلیسی)</p>
            </div>
            @endif

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:.625rem;cursor:pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $list?->is_active ?? true) ? 'checked' : '' }}
                           style="width:1rem;height:1rem;accent-color:#4f46e5">
                    <span class="form-label" style="margin:0">لیست فعال باشد</span>
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary">
                    <x-core-ui::icon name="check" class="w-4 h-4 ml-1" />
                    {{ $list ? 'ذخیره تغییرات' : 'ایجاد لیست' }}
                </button>
                <a href="{{ route('admin.access-lists.lists.index') }}" class="btn btn-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>
@endsection
