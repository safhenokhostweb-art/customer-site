@php
    $items = $items ?? collect();
    $menu = $menu ?? null;
    $itemTypes = $itemTypes ?? [];
    $pages = $pages ?? collect();
    $productCategories = $productCategories ?? collect();
    $blogCategories = $blogCategories ?? collect();
    $blogTags = $blogTags ?? collect();
@endphp
@foreach($items as $item)
    <li data-item-id="{{ $item->id }}" class="menu-sort-li border border-slate-200 rounded-lg bg-slate-50 p-2 mb-2 cursor-grab">
        <div class="flex flex-wrap justify-between gap-2 items-center">
            <span class="drag-handle text-slate-400 select-none" title="جابه‌جایی">⠿</span>
            <div class="flex-1 min-w-0">
                <strong>{{ $item->title }}</strong>
                <small class="text-slate-500">({{ $item->type }})</small>
                @if($item->badge_label)
                    <span class="text-xs bg-amber-100 text-amber-800 px-1 rounded">{{ $item->badge_label }}</span>
                @endif
            </div>
            <div class="flex flex-wrap gap-1">
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEdit({{ $item->id }})">ویرایش</button>
                <form method="post" action="{{ route('admin.menus.items.copy-branch', [$menu, $item]) }}" class="inline" onsubmit="return confirm('کپی کل شاخه؟');">
                    @csrf
                    <input type="hidden" name="target_parent_id" value="">
                    <button type="submit" class="btn btn-secondary btn-sm" title="کپی در ریشه">کپی شاخه</button>
                </form>
                <form method="post" action="{{ route('admin.menus.items.destroy', [$menu, $item]) }}" class="inline" onsubmit="return confirm('حذف این آیتم؟');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                </form>
            </div>
        </div>
        <div id="edit-{{ $item->id }}" class="hidden mt-3 p-3 bg-white rounded border border-slate-100">
            <form action="{{ route('admin.menus.items.update', [$menu, $item]) }}" method="post" class="admin-form-stack text-sm">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">والد</label>
                    <select name="parent_id">
                        <option value="">— ریشه —</option>
                        @foreach($allItems as $i)
                            @if($i->id !== $item->id)
                                <option value="{{ $i->id }}" {{ (int)$item->parent_id === (int)$i->id ? 'selected' : '' }}>
                                    {{ str_repeat('— ', $depthMap[$i->id] ?? 0) }}{{ $i->title }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>عنوان</label><input type="text" name="title" value="{{ old('title', $item->title) }}" required></div>
                <div class="form-group"><label>عنوان انگلیسی (اختیاری)</label><input type="text" name="title_en" value="{{ old('title_en', $item->title_en) }}"></div>
                <div class="form-group"><label>برچسب‌های JSON (اختیاری)</label><textarea name="labels_json" rows="2" placeholder='{"en":"..."}'>{{ old('labels_json', $item->labels ? json_encode($item->labels, JSON_UNESCAPED_UNICODE) : '') }}</textarea></div>
                <div class="form-group">
                    <label>نوع</label>
                    <select name="type" class="js-item-type" data-item="{{ $item->id }}">
                        @foreach($itemTypes as $val => $label)
                            <option value="{{ $val }}" {{ $item->type === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group ref-page-{{ $item->id }}" style="display:none;"><label>صفحه</label>
                    <select name="reference_id"><option value="">—</option>@foreach($pages as $p)<option value="{{ $p->id }}" {{ (int)$item->reference_id === (int)$p->id && $item->type === 'internal_page' ? 'selected' : '' }}>{{ $p->title }}</option>@endforeach</select>
                </div>
                <div class="form-group ref-cat-{{ $item->id }}" style="display:none;"><label>دسته محصول</label>
                    <select name="reference_id_category"><option value="">—</option>@foreach($productCategories as $c)<option value="{{ $c->id }}" {{ (int)$item->reference_id === (int)$c->id && $item->type === 'category' ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select>
                </div>
                <div class="form-group ref-blog-cat-{{ $item->id }}" style="display:none;"><label>دسته وبلاگ</label>
                    <select name="reference_id_blog_category"><option value="">—</option>@foreach($blogCategories as $c)<option value="{{ $c->id }}" {{ (int)$item->reference_id === (int)$c->id && $item->type === 'blog_category' ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select>
                </div>
                <div class="form-group ref-blog-tag-{{ $item->id }}" style="display:none;"><label>برچسب</label>
                    <select name="reference_id_blog_tag"><option value="">—</option>@foreach($blogTags as $t)<option value="{{ $t->id }}" {{ (int)$item->reference_id === (int)$t->id && $item->type === 'blog_tag' ? 'selected' : '' }}>{{ $t->name }}</option>@endforeach</select>
                </div>
                <div class="form-group url-f-{{ $item->id }}"><label>URL</label><input type="text" name="url" value="{{ old('url', $item->url) }}"></div>
                <div class="form-group"><label>UTM (رشته کوئری)</label><input type="text" name="utm_query" value="{{ old('utm_query', $item->utm_query) }}" placeholder="utm_source=..."></div>
                <div class="form-group"><label>UTM JSON</label><textarea name="utm_json" rows="2">{{ old('utm_json', $item->utm_json ? json_encode($item->utm_json, JSON_UNESCAPED_UNICODE) : '') }}</textarea></div>
                <div class="form-group"><label>آیکون / کلاس CSS</label><input type="text" name="icon" value="{{ old('icon', $item->icon) }}"></div>
                <div class="form-group"><label>برچسب (Badge)</label><input type="text" name="badge_label" value="{{ old('badge_label', $item->badge_label) }}"></div>
                <div class="form-group"><label>تصویر سرستون مگا</label><input type="text" name="image_url" value="{{ old('image_url', $item->image_url) }}" placeholder="https://..."></div>
                <div class="form-group"><label>ستون مگامنو (۰=پیش‌فرض)</label><input type="number" name="mega_column" value="{{ old('mega_column', $item->mega_column) }}" min="0" max="99"></div>
                <div class="form-group"><label>بلوک HTML ویژه</label><textarea id="menu-featured-html-{{ $item->id }}" class="menu-featured-html-editor w-full min-h-[120px]" name="featured_html" rows="2">{{ old('featured_html', $item->featured_html) }}</textarea></div>
                <div class="form-group"><label>کلاس CSS اضافی</label><input type="text" name="css_class" value="{{ old('css_class', $item->css_class) }}"></div>
                <div class="form-group"><label>ماژول‌های لازم (JSON آرایه slug)</label><textarea name="visible_modules_json" rows="1" placeholder='["shop","blog"]'>{{ old('visible_modules_json', $item->visible_modules ? json_encode($item->visible_modules, JSON_UNESCAPED_UNICODE) : '') }}</textarea></div>
                <label class="admin-form-inline-check"><input type="checkbox" name="open_in_new_tab" value="1" {{ $item->open_in_new_tab ? 'checked' : '' }}> تب جدید</label>
                <label class="admin-form-inline-check"><input type="checkbox" name="rel_nofollow" value="1" {{ $item->rel_nofollow ? 'checked' : '' }}> nofollow</label>
                <label class="admin-form-inline-check"><input type="checkbox" name="auto_list_children" value="1" {{ $item->auto_list_children ? 'checked' : '' }}> زیردسته‌های خودکار (فروشگاه)</label>
                <button type="submit" class="btn btn-primary btn-sm">ذخیره</button>
            </form>
        </div>
        @if($item->children->count() > 0)
            <ol class="menu-nested-children list-none pr-4 mt-2 border-r-2 border-slate-200">
                @include('admin.menus.partials.item-sortable', ['items' => $item->children, 'menu' => $menu, 'itemTypes' => $itemTypes, 'pages' => $pages, 'productCategories' => $productCategories, 'blogCategories' => $blogCategories, 'blogTags' => $blogTags, 'allItems' => $allItems, 'depthMap' => $depthMap])
            </ol>
        @endif
    </li>
@endforeach
