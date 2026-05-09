@extends('admin.layout')
@section('title', 'ویرایش ویژگی')
@section('content')
<div class="admin-page">
    <div class="admin-page-head"><h1 class="admin-page-title">ویرایش: {{ $productAttribute->name }}</h1></div>
    <div class="card"><div class="card-body">
        <form method="post" action="{{ route('admin.product-attributes.update', $productAttribute) }}">
            @csrf @method('PUT')
            <fieldset class="admin-fieldset">
                <legend class="admin-fieldset__legend">اطلاعات ویژگی</legend>
                <div class="admin-form-grid admin-form-grid--2">
                    <div class="form-group">
                        <label class="form-label" for="attribute-name">نام</label>
                        <input id="attribute-name" type="text" name="name" value="{{ old('name', $productAttribute->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="attribute-slug">نامک</label>
                        <input id="attribute-slug" type="text" name="slug" value="{{ old('slug', $productAttribute->slug) }}">
                    </div>
                </div>
                <div class="admin-form-grid admin-form-grid--2">
                    <div class="form-group">
                        <label class="admin-form-inline-check" for="attribute-filterable">
                            <input id="attribute-filterable" type="checkbox" name="filterable" value="1" @checked(old('filterable', $productAttribute->filterable))> قابل فیلتر
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="attribute-sort-order">ترتیب</label>
                        <input id="attribute-sort-order" type="number" name="sort_order" value="{{ old('sort_order', $productAttribute->sort_order) }}" min="0">
                    </div>
                </div>
            </fieldset>
            <fieldset class="admin-fieldset">
            <legend class="admin-fieldset__legend">مقادیر</legend>
            @php $vals = old('values', $productAttribute->values->map(fn($v) => $v->toArray())->values()->all()); @endphp
            @if(empty($vals))
                @php $vals = [['id' => '', 'value' => '', 'slug' => '', 'sort_order' => 0]]; @endphp
            @endif
            @foreach($vals as $i => $row)
                <div class="admin-soft-panel mb-2">
                    <input type="hidden" name="values[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                    <div class="grid gap-2" style="grid-template-columns:1fr 1fr auto;">
                        <input type="text" name="values[{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" placeholder="مقدار">
                        <input type="text" name="values[{{ $i }}][slug]" value="{{ $row['slug'] ?? '' }}" placeholder="نامک (اختیاری)">
                        <input type="number" name="values[{{ $i }}][sort_order]" value="{{ $row['sort_order'] ?? $i }}" class="w-20" placeholder="ترتیب">
                    </div>
                </div>
            @endforeach
            </fieldset>
            <div class="admin-form-actions admin-form-actions--sticky">
                <button type="submit" class="btn btn-primary">ذخیره</button>
                <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary">انصراف</a>
            </div>
        </form>
    </div></div>
</div>
@endsection
