@php $item = $item ?? null; @endphp
@if($item)
<li style="margin: 0.5rem 0; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #f9fafb;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <span><strong>{{ $item->title }}</strong> <small style="color: #6b7280;">({{ $item->type }})</small></span>
        <div style="display: flex; gap: 0.25rem;">
            <a href="#" class="btn btn-secondary" style="font-size: 0.85rem;" onclick="document.getElementById('edit-{{ $item->id }}').style.display = document.getElementById('edit-{{ $item->id }}').style.display === 'none' ? 'block' : 'none'; return false;">ویرایش</a>
            <form method="post" action="{{ route('admin.menus.items.destroy', [$menu ?? $item->menu, $item]) }}" style="display:inline;" onsubmit="return confirm('حذف این آیتم؟');">@csrf @method('DELETE')<button type="submit" class="btn btn-danger" style="font-size: 0.85rem;">حذف</button></form>
        </div>
    </div>
    <div id="edit-{{ $item->id }}" style="display: none; margin-top: 0.75rem; padding: 0.75rem; background: #fff; border-radius: 0.5rem;">
        <form action="{{ route('admin.menus.items.update', [$menu ?? $item->menu, $item]) }}" method="post">
            @csrf
            @method('PUT')
            <div class="form-group"><label>عنوان</label><input type="text" name="title" value="{{ old('title', $item->title) }}" required></div>
            <div class="form-group">
                <label>نوع</label>
                <select name="type">
                    @foreach($itemTypes ?? ['custom_url'=>'لینک دلخواه','internal_page'=>'صفحه داخلی','blog'=>'وبلاگ','shop'=>'فروشگاه','category'=>'دسته‌بندی محصول'] as $val => $label)
                        <option value="{{ $val }}" {{ $item->type === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label>آدرس / شناسه</label><input type="text" name="url" value="{{ old('url', $item->url) }}" placeholder="URL یا خالی"></div>
            <div class="form-group"><label>reference_id (صفحه یا دسته)</label><input type="number" name="reference_id" value="{{ old('reference_id', $item->reference_id) }}" min="0" placeholder="ID"></div>
            <div class="form-group"><label><input type="checkbox" name="open_in_new_tab" value="1" {{ $item->open_in_new_tab ? 'checked' : '' }}> باز در تب جدید</label></div>
            <button type="submit" class="btn btn-primary">ذخیره</button>
        </form>
    </div>
    @if($item->children->count() > 0)
        <ul style="list-style: none; padding-right: 1.5rem; margin-top: 0.5rem;">
            @foreach($item->children as $child)
                @include('admin.menus.partials.item', ['item' => $child, 'menu' => $menu ?? $item->menu, 'itemTypes' => $itemTypes ?? []])
            @endforeach
        </ul>
    @endif
</li>
@endif
