@extends('admin.layout')
@section('title', 'لینک شبکه اجتماعی جدید')
@section('header', 'لینک شبکه اجتماعی جدید')
@section('content')
<div class="max-w-xl">
    <div class="card">
        <div class="card-header">افزودن لینک</div>
        <div class="card-body">
            <form action="{{ route('admin.social-links.store') }}" method="post">
                @csrf
                <div class="form-group">
                    <label class="form-label">پلتفرم *</label>
                    <select name="platform" required>
                        <option value="">انتخاب کنید</option>
                        @foreach($platforms as $key => $label)
                            <option value="{{ $key }}" {{ old('platform') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">آدرس URL *</label>
                    <input type="url" name="url" value="{{ old('url') }}" required placeholder="https://">
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <span>لینک فعال است</span>
                    </label>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره لینک</button>
                    <a href="{{ route('admin.social-links.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
