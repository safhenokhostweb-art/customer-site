@extends('admin.layout')
@section('title', 'ویرایش منو: ' . $menu->name)
@section('content')
<h1>ویرایش منو: {{ $menu->name }}</h1>
<p style="color: #6b7280; margin-bottom: 1rem;">موقعیت: {{ \App\Models\Menu::locations()[$menu->location] ?? $menu->location }}</p>
@if(session('success'))
    <p style="color: green; margin-bottom: 1rem;">{{ session('success') }}</p>
@endif

<div style="display: flex; flex-wrap: wrap; gap: 2rem; align-items: flex-start;">
    <div style="flex: 1; min-width: 280px;">
        <h2 style="font-size: 1.1rem;">آیتم‌های منو</h2>
        <ul id="menu-tree" style="list-style: none; padding: 0;">
            @foreach($menu->allItems()->whereNull('parent_id')->orderBy('order')->get() as $item)
                @include('admin.menus.partials.item', ['item' => $item, 'menu' => $menu, 'itemTypes' => $itemTypes])
            @endforeach
        </ul>
        @if($menu->allItems()->count() === 0)
            <p style="color: #6b7280;">هنوز آیتمی اضافه نشده. از فرم سمت چپ استفاده کنید.</p>
        @endif
    </div>

    <div style="width: 320px;">
        <h2 style="font-size: 1.1rem;">افزودن آیتم</h2>
        <form action="{{ route('admin.menus.items.store', $menu) }}" method="post" style="display: flex; flex-direction: column; gap: 0.75rem;">
            @csrf
            <div class="form-group">
                <label>والد (زیرمنو)</label>
                <select name="parent_id">
                    <option value="">— بدون والد (سطح اول) —</option>
                    @foreach($menu->allItems()->orderBy('order')->get() as $i)
                        <option value="{{ $i->id }}">{{ str_repeat('— ', $i->parent_id ? 1 : 0) }}{{ $i->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>عنوان</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>نوع</label>
                <select name="type" id="new-item-type">
                    @foreach($itemTypes as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" id="new-ref-page" style="display: none;">
                <label>صفحه</label>
                <select name="reference_id_page">
                    <option value="">— انتخاب —</option>
                    @foreach($pages as $p)
                        <option value="{{ $p->id }}">{{ $p->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" id="new-ref-category" style="display: none;">
                <label>دسته‌بندی محصول</label>
                <select name="reference_id_category">
                    <option value="">— انتخاب —</option>
                    @foreach($productCategories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" id="new-url">
                <label>آدرس (URL)</label>
                <input type="text" name="url" placeholder="https://... یا #">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="open_in_new_tab" value="1"> باز شدن در تب جدید</label>
            </div>
            <button type="submit" class="btn btn-primary">افزودن</button>
        </form>
    </div>
</div>

<hr style="margin: 2rem 0;">
<form action="{{ route('admin.menus.update', $menu) }}" method="post" style="max-width: 400px;">
    @csrf
    @method('PUT')
    <div class="form-group"><label>نام منو</label><input type="text" name="name" value="{{ old('name', $menu->name) }}" required></div>
    <button type="submit" class="btn btn-secondary">به‌روزرسانی نام منو</button>
</form>

<script>
document.getElementById('new-item-type').addEventListener('change', function() {
    var type = this.value;
    document.getElementById('new-ref-page').style.display = type === 'internal_page' ? 'block' : 'none';
    document.getElementById('new-ref-category').style.display = type === 'category' ? 'block' : 'none';
    document.getElementById('new-url').style.display = (type === 'custom_url' || type === 'internal_page' || type === 'category') ? 'block' : 'none';
});
document.getElementById('new-item-type').dispatchEvent(new Event('change'));
</script>
@endsection
