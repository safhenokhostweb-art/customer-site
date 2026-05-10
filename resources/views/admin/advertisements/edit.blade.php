@extends('admin.layout')
@section('title', 'ویرایش تبلیغ')
@section('header', 'ویرایش تبلیغ')
@section('content')
<div class="admin-page max-w-2xl">
    <div class="card">
        <div class="card-header">اطلاعات تبلیغ</div>
        <div class="card-body">
            <form action="{{ route('admin.advertisements.update', $advertisement) }}" method="post">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">نوع تبلیغ *</label>
                        <select name="type" required>
                            @foreach(\App\Models\Advertisement::typeLabels() as $k => $v)
                                <option value="{{ $k }}" {{ old('type', $advertisement->type) === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">موقعیت نمایش *</label>
                        <select name="position" required>
                            @foreach(\App\Models\Advertisement::positionLabels() as $k => $v)
                                <option value="{{ $k }}" {{ old('position', $advertisement->position) === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">عنوان</label>
                    <input type="text" name="title" value="{{ old('title', $advertisement->title) }}" placeholder="عنوان تبلیغ">
                </div>
                <div class="form-group">
                    <label class="form-label">متن تبلیغ</label>
                    <textarea id="advertisement-content-editor" name="content" class="w-full min-h-[160px]" placeholder="متن برای نوشتار رونده، تایپی، مارکی و...">{{ old('content', $advertisement->content) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">تصویر (برای بنر)</label>
                    @include('components.image-picker', ['name' => 'image_path', 'value' => old('image_path', $advertisement->image_path), 'context' => 'advertisement', 'label' => 'تصویر'])
                </div>
                <div class="form-group">
                    <label class="form-label">لینک</label>
                    <input type="text" name="link" value="{{ old('link', $advertisement->link) }}" placeholder="https://">
                </div>
                <div class="form-group">
                    <label class="form-label">ترتیب نمایش</label>
                    <input type="number" name="order" value="{{ old('order', $advertisement->order) }}" min="0">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">تاریخ شروع (شمسی)</label>
                        <x-persian-date-input name="starts_at" :value="old('starts_at', $advertisement->starts_at ? \App\Helpers\Jalali::formatFa($advertisement->starts_at, 'Y/m/d') : '')" placeholder="۱۴۰۴/۱۲/۰۵" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">تاریخ انقضا (شمسی)</label>
                        <x-persian-date-input name="expires_at" :value="old('expires_at', $advertisement->expires_at ? \App\Helpers\Jalali::formatFa($advertisement->expires_at, 'Y/m/d') : '')" placeholder="۱۴۰۴/۱۲/۳۰" class="form-control" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $advertisement->is_active) ? 'checked' : '' }}>
                        <span>تبلیغ فعال است</span>
                    </label>
                </div>
                <div class="admin-page-tools mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                    <a href="{{ route('admin.advertisements.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.partials.tinymce-field-init', ['selector' => '#advertisement-content-editor', 'uploadContext' => 'advertisement', 'height' => 280])
@endsection
