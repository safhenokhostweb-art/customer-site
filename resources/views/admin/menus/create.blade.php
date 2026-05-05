@extends('admin.layout')
@section('title', 'ایجاد منو')
@section('content')
<h1>ایجاد منو</h1>
@if(session('error'))
    <p style="color: #b91c1c; margin-bottom: 1rem;">{{ session('error') }}</p>
@endif
<form action="{{ route('admin.menus.store') }}" method="post">
    @csrf
    <div class="form-group">
        <label>نام منو</label>
        <input type="text" name="name" value="{{ old('name') }}" required placeholder="مثلاً منوی اصلی">
    </div>
    <div class="form-group">
        <label>موقعیت</label>
        <select name="location" required>
            @foreach($locations as $key => $label)
                <option value="{{ $key }}" {{ old('location') === $key ? 'selected' : '' }}>{{ $label }}{{ in_array($key, $existingLocations ?? []) ? ' (قبلاً استفاده شده)' : '' }}</option>
            @endforeach
        </select>
        <small style="color: #6b7280;">هر موقعیت فقط یک منو می‌تواند داشته باشد.</small>
    </div>
    <button type="submit" class="btn btn-primary">ذخیره</button>
    <a href="{{ route('admin.menus.index') }}" class="btn btn-secondary">انصراف</a>
</form>
@endsection
