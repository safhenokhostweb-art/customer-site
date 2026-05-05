@extends('admin.layout')
@section('title', 'ایجاد فرم جدید')
@section('header', 'ایجاد فرم جدید')
@section('content')

<div class="card max-w-xl">
    <div class="card-body">
        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.forms.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="form-label">عنوان فرم</label>
                <input type="text" name="title" class="w-full border rounded-lg px-3 py-2" required>
            </div>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" checked>
                <span>فعال باشد</span>
            </label>

            <button type="submit" class="btn btn-primary">ذخیره فرم</button>
        </form>
    </div>
</div>

@endsection
