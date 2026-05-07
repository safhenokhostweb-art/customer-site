@extends('admin.layout')
@section('title', 'ویرایش منو: ' . $menu->name)
@section('header', 'ویرایش منو')
@section('content')
<div class="admin-page">
    <p class="text-slate-500">ویرایش «{{ $menu->name }}» — موقعیت: {{ \App\Models\Menu::locations()[$menu->location] ?? $menu->location }}</p>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <div class="xl:col-span-2 card">
            <div class="card-header">آیتم‌های منو</div>
            <div class="card-body">
                <ul id="menu-tree" class="space-y-2 p-0 m-0 list-none">
            @foreach($menu->allItems()->whereNull('parent_id')->orderBy('order')->get() as $item)
                @include('admin.menus.partials.item', ['item' => $item, 'menu' => $menu, 'itemTypes' => $itemTypes])
            @endforeach
                </ul>
                @if($menu->allItems()->count() === 0)
                    <p class="text-slate-500 mt-3">هنوز آیتمی اضافه نشده. از فرم کنار صفحه استفاده کنید.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">افزودن آیتم</div>
            <div class="card-body">
                <form action="{{ route('admin.menus.items.store', $menu) }}" method="post">
            @csrf
            <div class="form-group">
                <label class="form-label">والد (زیرمنو)</label>
                <select name="parent_id">
                    <option value="">— بدون والد (سطح اول) —</option>
                    @foreach($menu->allItems()->orderBy('order')->get() as $i)
                        <option value="{{ $i->id }}">{{ str_repeat('— ', $i->parent_id ? 1 : 0) }}{{ $i->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">عنوان</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label class="form-label">نوع</label>
                <select name="type" id="new-item-type">
                    @foreach($itemTypes as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" id="new-ref-page" style="display: none;">
                <label class="form-label">صفحه</label>
                <select name="reference_id_page">
                    <option value="">— انتخاب —</option>
                    @foreach($pages as $p)
                        <option value="{{ $p->id }}">{{ $p->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" id="new-ref-category" style="display: none;">
                <label class="form-label">دسته‌بندی محصول</label>
                <select name="reference_id_category">
                    <option value="">— انتخاب —</option>
                    @foreach($productCategories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" id="new-url">
                <label class="form-label">آدرس (URL)</label>
                <input type="text" name="url" placeholder="https://... یا #">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="open_in_new_tab" value="1"> باز شدن در تب جدید</label>
            </div>
            <div class="admin-page-tools">
                <button type="submit" class="btn btn-primary">افزودن</button>
            </div>
        </form>
            </div>
        </div>
    </div>

    <div class="card max-w-md">
        <div class="card-header">به‌روزرسانی نام منو</div>
        <div class="card-body">
            <form action="{{ route('admin.menus.update', $menu) }}" method="post">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">نام منو</label>
                    <input type="text" name="name" value="{{ old('name', $menu->name) }}" required>
                </div>
                <div class="admin-page-tools">
                    <button type="submit" class="btn btn-secondary">به‌روزرسانی نام منو</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var typeSelect = document.getElementById('new-item-type');
    if (!typeSelect) return;
    function toggleMenuTypeRefs() {
        var type = typeSelect.value;
        document.getElementById('new-ref-page').style.display = type === 'internal_page' ? 'block' : 'none';
        document.getElementById('new-ref-category').style.display = type === 'category' ? 'block' : 'none';
        document.getElementById('new-url').style.display = (type === 'custom_url' || type === 'internal_page' || type === 'category') ? 'block' : 'none';
    }
    typeSelect.addEventListener('change', toggleMenuTypeRefs);
    toggleMenuTypeRefs();
});
</script>
@endpush
@endsection
