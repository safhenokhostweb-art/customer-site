@extends('admin.layout')
@section('title', 'ویرایش فرم: ' . $form->title)
@section('header', 'ویرایش فرم: ' . $form->title)
@section('content')

@if(session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4">{{ session('success') }}</div>
@endif

<p class="mb-4">
    <a href="{{ route('admin.forms.preview', $form) }}" class="btn btn-primary" target="_blank">پیش‌نمایش فرم</a>
    <a href="{{ route('admin.forms.index') }}" class="btn btn-secondary mr-2">بازگشت به لیست</a>
</p>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                    <ul class="list-disc pr-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <h2 class="font-bold mb-4">افزودن فیلد جدید</h2>

            <form method="POST" action="{{ route('admin.forms.fields.store', $form) }}" class="space-y-3">
                @csrf

                <div>
                    <label class="form-label">عنوان فیلد</label>
                    <input name="label" class="w-full border rounded px-3 py-2" placeholder="مثال: نام" required>
                </div>
                <div>
                    <label class="form-label">نام فیلد (name)</label>
                    <input name="name" class="w-full border rounded px-3 py-2" placeholder="مثال: name" required>
                </div>
                <div>
                    <label class="form-label">نوع</label>
                    <select name="type" class="w-full border rounded px-3 py-2">
                        <option value="text">متن</option>
                        <option value="textarea">متن بلند</option>
                        <option value="email">ایمیل</option>
                        <option value="number">عدد</option>
                        <option value="date">تاریخ (شمسی)</option>
                        <option value="radio">رادیو</option>
                        <option value="checkbox">چک‌باکس</option>
                        <option value="select">لیست انتخابی</option>
                    </select>
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_required" value="1">
                    <span>الزامی</span>
                </label>

                <button type="submit" class="btn btn-primary">افزودن فیلد</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="font-bold mb-4">فیلدهای فرم</h2>

            <form method="POST" action="{{ route('admin.forms.update', $form) }}" class="mb-4">
                @csrf
                @method('PUT')
                <div class="space-y-2 mb-3">
                    <div>
                        <label class="form-label">عنوان فرم</label>
                        <input type="text" name="title" class="w-full border rounded px-3 py-2" value="{{ old('title', $form->title) }}" required>
                    </div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" {{ $form->is_active ? 'checked' : '' }}>
                        <span>فعال</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">ذخیره تنظیمات فرم</button>
            </form>

            <ul class="space-y-2">
                @foreach($form->fields()->orderBy('position')->get() as $field)
                    <li class="flex justify-between items-center border rounded p-2">
                        <span>{{ $field->label }} ({{ $field->type }})</span>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('admin.forms.fields.toggle', $field) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-2 py-1 text-xs rounded bg-gray-200">
                                    {{ $field->is_active ? 'غیرفعال' : 'فعال' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.forms.fields.destroy', $field) }}" class="inline" onsubmit="return confirm('حذف فیلد؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm px-2 py-1 bg-red-500 text-white rounded">حذف</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if($form->fields->isEmpty())
                <p class="text-slate-500 text-sm">هنوز فیلدی اضافه نشده است.</p>
            @endif
        </div>
    </div>
</div>

@endsection
