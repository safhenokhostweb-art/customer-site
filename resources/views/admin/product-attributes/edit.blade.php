@extends('admin.layout')
@section('title', 'ویرایش ویژگی')
@section('content')
<div class="admin-page">
    <div class="admin-page-head"><h1 class="admin-page-title">ویرایش: {{ $productAttribute->name }}</h1></div>
    <div class="card"><div class="card-body">
        <form method="post" action="{{ route('admin.product-attributes.update', $productAttribute) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">نام</label>
                <input type="text" name="name" value="{{ old('name', $productAttribute->name) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">نامک</label>
                <input type="text" name="slug" value="{{ old('slug', $productAttribute->slug) }}">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="filterable" value="1" @checked(old('filterable', $productAttribute->filterable))> قابل فیلتر</label>
            </div>
            <div class="form-group">
                <label class="form-label">ترتیب</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $productAttribute->sort_order) }}" min="0">
            </div>
            <h2 class="text-base font-bold mt-6 mb-2">مقادیر</h2>
            @php $vals = old('values', $productAttribute->values->map(fn($v) => $v->toArray())->values()->all()); @endphp
            @if(empty($vals))
                @php $vals = [['id' => '', 'value' => '', 'slug' => '', 'sort_order' => 0]]; @endphp
            @endif
            @foreach($vals as $i => $row)
                <div class="border rounded p-3 mb-2" style="background:#f9fafb;">
                    <input type="hidden" name="values[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                    <div class="grid gap-2" style="grid-template-columns:1fr 1fr auto;">
                        <input type="text" name="values[{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" placeholder="مقدار">
                        <input type="text" name="values[{{ $i }}][slug]" value="{{ $row['slug'] ?? '' }}" placeholder="نامک (اختیاری)">
                        <input type="number" name="values[{{ $i }}][sort_order]" value="{{ $row['sort_order'] ?? $i }}" class="w-20" placeholder="ترتیب">
                    </div>
                </div>
            @endforeach
            <button type="submit" class="btn btn-primary">ذخیره</button>
            <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary">انصراف</a>
        </form>
    </div></div>
</div>
@endsection
