@extends('admin.layout')
@section('title', 'ویرایش منو: ' . $menu->name)
@section('header', 'ویرایش منو')
@section('content')
<div class="admin-page">
    @if(session('new_preview_token'))
        <div class="card mb-4 border-blue-200 bg-blue-50 text-sm">توکن جدید: <code>{{ session('new_preview_token') }}</code></div>
    @endif
    <p class="admin-section-lead">«{{ $menu->name }}» — {{ \App\Models\Menu::locations()[$menu->location] ?? $menu->location }} — وضعیت:
        <strong>{{ $menu->status === 'published' ? 'منتشر شده' : 'پیش‌نویس' }}</strong>
        @if($menu->go_live_at) — زمان انتشار: {{ $menu->go_live_at->format('Y-m-d H:i') }} @endif
    </p>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <div class="xl:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header flex flex-wrap justify-between gap-2 items-center">
                    <span>آیتم‌ها (کشیدن و رها کردن)</span>
                    <span class="text-xs text-slate-500">حداکثر عمق: {{ $maxDepth }}</span>
                </div>
                <div class="card-body">
                    <form id="menu-reorder-form" action="{{ route('admin.menus.reorder-tree', $menu) }}" method="post" class="mb-3">
                        @csrf
                        <input type="hidden" name="nodes" id="menu-nodes-json" value="">
                        <button type="submit" class="btn btn-secondary btn-sm">ذخیرهٔ ترتیب درخت</button>
                    </form>
                    <ol id="menu-sortable-root" class="menu-nested-children list-none p-0 m-0">
                        @include('admin.menus.partials.item-sortable', [
                            'items' => $menu->items,
                            'menu' => $menu,
                            'itemTypes' => $itemTypes,
                            'pages' => $pages,
                            'productCategories' => $productCategories,
                            'blogCategories' => $blogCategories,
                            'blogTags' => $blogTags,
                            'allItems' => $allItems,
                            'depthMap' => $depthMap,
                        ])
                    </ol>
                    @if($menu->allItems()->count() === 0)
                        <p class="text-slate-500 mt-3">هنوز آیتمی نیست.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">تنظیمات انتشار و پیش‌نمایش</div>
                <div class="card-body space-y-4">
                    <form action="{{ route('admin.menus.update', $menu) }}" method="post" class="admin-form-stack">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label class="form-label">نام منو</label>
                            <input type="text" name="name" value="{{ old('name', $menu->name) }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">وضعیت</label>
                            <select name="status">
                                <option value="draft" @selected(old('status', $menu->status) === 'draft')>پیش‌نویس</option>
                                <option value="published" @selected(old('status', $menu->status) === 'published')>منتشر شده</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">زمان انتشار (اختیاری — آینده برای زمان‌بندی)</label>
                            <input type="datetime-local" name="go_live_at" value="{{ old('go_live_at', optional($menu->go_live_at)->format('Y-m-d\TH:i')) }}">
                        </div>
                        <button type="submit" class="btn btn-secondary">ذخیرهٔ وضعیت</button>
                    </form>
                    <form action="{{ route('admin.menus.publish', $menu) }}" method="post" class="admin-form-stack border-t border-slate-100 pt-4">
                        @csrf
                        <p class="text-sm text-slate-600">با انتشار، نسخهٔ پشتیبان از درخت فعلی ذخیره می‌شود.</p>
                        <div class="form-group">
                            <label class="form-label">یادداشت نسخه (اختیاری)</label>
                            <input type="text" name="revision_note" value="{{ old('revision_note') }}">
                        </div>
                        <button type="submit" class="btn btn-primary">انتشار و ذخیرهٔ نسخه</button>
                    </form>
                    <div class="border-t border-slate-100 pt-4 text-sm space-y-2">
                        <form action="{{ route('admin.menus.preview-token', $menu) }}" method="post" class="inline">@csrf<button type="submit" class="btn btn-secondary btn-sm">بازتولید لینک پیش‌نمایش</button></form>
                        @if($previewUrl)
                            <div><a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="text-blue-600 underline break-all">پیش‌نمایش</a></div>
                        @endif
                    </div>
                </div>
            </div>

            @if($revisions->count())
                <div class="card">
                    <div class="card-header">نسخه‌های قبلی</div>
                    <div class="card-body text-sm space-y-2">
                        @foreach($revisions as $rev)
                            <div class="flex flex-wrap justify-between gap-2 border-b border-slate-100 pb-2">
                                <span>{{ $rev->created_at->format('Y-m-d H:i') }} @if($rev->note) — {{ $rev->note }} @endif</span>
                                <form action="{{ route('admin.menus.revisions.restore', [$menu, $rev]) }}" method="post" onsubmit="return confirm('بازگردانی این نسخه؟ آیتم‌های فعلی جایگزین می‌شوند.');">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm">بازگردانی</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header">افزودن آیتم</div>
            <div class="card-body">
                <form action="{{ route('admin.menus.items.store', $menu) }}" method="post" class="admin-form-stack">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">والد</label>
                        <select name="parent_id">
                            <option value="">— بدون والد —</option>
                            @foreach($allItems as $i)
                                <option value="{{ $i->id }}">{{ str_repeat('— ', $depthMap[$i->id] ?? 0) }}{{ $i->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label">عنوان</label><input type="text" name="title" required></div>
                    <div class="form-group"><label class="form-label">عنوان انگلیسی</label><input type="text" name="title_en"></div>
                    <div class="form-group"><label class="form-label">برچسب‌های JSON</label><textarea name="labels_json" rows="2" placeholder='{"en":"..."}'></textarea></div>
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
                            <option value="">—</option>
                            @foreach($pages as $p)
                                <option value="{{ $p->id }}">{{ $p->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="new-ref-category" style="display: none;">
                        <label class="form-label">دسته محصول</label>
                        <select name="reference_id_category">
                            <option value="">—</option>
                            @foreach($productCategories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="new-ref-blog-cat" style="display: none;">
                        <label class="form-label">دسته وبلاگ</label>
                        <select name="reference_id_blog_category">
                            <option value="">—</option>
                            @foreach($blogCategories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="new-ref-blog-tag" style="display: none;">
                        <label class="form-label">برچسب</label>
                        <select name="reference_id_blog_tag">
                            <option value="">—</option>
                            @foreach($blogTags as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="new-url">
                        <label class="form-label">URL</label>
                        <input type="text" name="url" placeholder="https://... یا /path">
                    </div>
                    <div class="form-group"><label class="form-label">UTM کوئری</label><input type="text" name="utm_query" placeholder="utm_source=..."></div>
                    <div class="form-group"><label class="form-label">UTM JSON</label><textarea name="utm_json" rows="2"></textarea></div>
                    <div class="form-group"><label class="form-label">آیکون</label><input type="text" name="icon"></div>
                    <div class="form-group"><label class="form-label">Badge</label><input type="text" name="badge_label"></div>
                    <div class="form-group"><label class="form-label">تصویر مگا</label><input type="text" name="image_url"></div>
                    <div class="form-group"><label class="form-label">ستون مگا</label><input type="number" name="mega_column" min="0" max="99"></div>
                    <div class="form-group"><label class="form-label">HTML ویژه</label><textarea name="featured_html" rows="2"></textarea></div>
                    <div class="form-group"><label class="form-label">کلاس CSS</label><input type="text" name="css_class"></div>
                    <div class="form-group"><label class="form-label">ماژول‌های لازم JSON</label><textarea name="visible_modules_json" rows="1" placeholder='["shop"]'></textarea></div>
                    <label class="admin-form-inline-check"><input type="checkbox" name="open_in_new_tab" value="1"> تب جدید</label>
                    <label class="admin-form-inline-check"><input type="checkbox" name="rel_nofollow" value="1"> nofollow</label>
                    <label class="admin-form-inline-check"><input type="checkbox" name="auto_list_children" value="1"> زیردسته خودکار فروشگاه</label>
                    <div class="admin-page-tools">
                        <button type="submit" class="btn btn-primary">افزودن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script defer src="{{ asset('vendor/sortablejs/Sortable.min.js') }}"></script>
<script>
function toggleEdit(id) {
    var el = document.getElementById('edit-' + id);
    if (el) el.classList.toggle('hidden');
}
function toggleNewItemRefs() {
    var type = document.getElementById('new-item-type').value;
    document.getElementById('new-ref-page').style.display = type === 'internal_page' ? 'block' : 'none';
    document.getElementById('new-ref-category').style.display = type === 'category' ? 'block' : 'none';
    document.getElementById('new-ref-blog-cat').style.display = type === 'blog_category' ? 'block' : 'none';
    document.getElementById('new-ref-blog-tag').style.display = type === 'blog_tag' ? 'block' : 'none';
    document.getElementById('new-url').style.display = (type === 'custom_url') ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', function() {
    var nt = document.getElementById('new-item-type');
    if (nt) { nt.addEventListener('change', toggleNewItemRefs); toggleNewItemRefs(); }

    document.querySelectorAll('.js-item-type').forEach(function(sel) {
        function sync() {
            var id = sel.getAttribute('data-item');
            var type = sel.value;
            var show = function(cls, on) {
                var el = document.querySelector('.' + cls + '-' + id);
                if (el) el.style.display = on ? 'block' : 'none';
            };
            show('ref-page', type === 'internal_page');
            show('ref-cat', type === 'category');
            show('ref-blog-cat', type === 'blog_category');
            show('ref-blog-tag', type === 'blog_tag');
            var urlEl = document.querySelector('.url-f-' + id);
            if (urlEl) urlEl.style.display = (type === 'custom_url' || type === 'internal_page' || type === 'category') ? 'block' : 'none';
        }
        sel.addEventListener('change', sync);
        sync();
    });

    function mountSortable(ol) {
        if (typeof Sortable === 'undefined') return;
        Sortable.create(ol, {
            group: 'nested',
            animation: 150,
            handle: '.drag-handle',
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onEnd: function() { serializeTree(); }
        });
        ol.querySelectorAll(':scope > li > ol.menu-nested-children').forEach(function(childOl) {
            mountSortable(childOl);
        });
    }
    var root = document.getElementById('menu-sortable-root');
    function serializeList(ol, parentId) {
        var out = [];
        var lis = ol.querySelectorAll(':scope > li.menu-sort-li');
        lis.forEach(function(li, idx) {
            var id = parseInt(li.getAttribute('data-item-id'), 10);
            out.push({ id: id, parent_id: parentId, order: idx });
            var nest = li.querySelector(':scope > ol.menu-nested-children');
            if (nest) {
                out = out.concat(serializeList(nest, id));
            }
        });
        return out;
    }
    function serializeTree() {
        if (!root) return;
        var data = serializeList(root, null);
        document.getElementById('menu-nodes-json').value = JSON.stringify(data);
    }
    if (root) {
        mountSortable(root);
        serializeTree();
        document.getElementById('menu-reorder-form').addEventListener('submit', function() { serializeTree(); });
    }
});
</script>
@endpush
@endsection
