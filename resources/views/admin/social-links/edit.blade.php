@extends('admin.layout')
@section('title', 'ویرایش لینک')
@section('header', 'ویرایش لینک')
@section('content')
<div class="max-w-xl">
    <div class="card">
        <div class="card-header">ویرایش لینک {{ \App\Models\SocialLink::platforms()[$socialLink->platform] ?? $socialLink->platform }}</div>
        <div class="card-body">
            <form action="{{ route('admin.social-links.update', $socialLink) }}" method="post">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">پلتفرم *</label>
                    <select name="platform" required>
                        @foreach($platforms as $key => $label)
                            <option value="{{ $key }}" {{ old('platform', $socialLink->platform) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">آدرس URL *</label>
                    <input type="url" name="url" value="{{ old('url', $socialLink->url) }}" required>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $socialLink->is_active) ? 'checked' : '' }}>
                        <span>لینک فعال است</span>
                    </label>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                    <a href="{{ route('admin.social-links.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
