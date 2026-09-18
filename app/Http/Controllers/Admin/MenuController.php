<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuChangeLog;
use App\Models\MenuItem;
use App\Models\MenuRevision;
use App\Models\Page;
use App\Models\ProductCategory;
use App\Models\Tag;
use App\Services\MenuHierarchyService;
use App\Services\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    protected MenuHierarchyService $hierarchy;

    public function __construct(
        protected MenuService $menuService,
    ) {
        $this->hierarchy = MenuHierarchyService::fromConfig();
    }

    public function index(Request $request): View
    {
        $q = Menu::query()->orderBy('location');
        if ($request->filled('location')) {
            $q->where('location', $request->string('location'));
        }
        $menus = $q->get();
        $locations = Menu::locations();

        return view('admin.menus.index', compact('menus', 'locations'));
    }

    public function create(): View
    {
        $locations = Menu::selectableLocations();
        $existingLocations = Menu::whereNull('site_id')->pluck('location')->toArray();

        return view('admin.menus.create', compact('locations', 'existingLocations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => 'required|string|max:255',
            'location' => 'required|string|in:primary,footer',
        ];
        if (Menu::menusTableHasStatusColumn()) {
            $rules['status'] = 'nullable|string|in:draft,published';
        }
        if (Menu::menusTableHasGoLiveAtColumn()) {
            $rules['go_live_at'] = 'nullable|date';
            if ($request->input('go_live_at') === '') {
                $request->merge(['go_live_at' => null]);
            }
        }
        $data = $request->validate($rules);
        $data['site_id'] = null;
        if (Menu::menusTableHasStatusColumn()) {
            $data['status'] = $data['status'] ?? Menu::STATUS_PUBLISHED;
        }
        if (Menu::menusTableHasGoLiveAtColumn()) {
            if (! empty($data['go_live_at'])) {
                $data['go_live_at'] = \Carbon\Carbon::parse($data['go_live_at']);
            } else {
                $data['go_live_at'] = null;
            }
        }
        if (Menu::where('location', $data['location'])->whereNull('site_id')->exists()) {
            return back()->withInput()->with('error', 'یک منو برای این موقعیت از قبل وجود دارد.');
        }
        $menu = Menu::create(Menu::stripUnsupportedMenuTableAttributes($data));
        if (Menu::menusTableHasPreviewTokenColumn()) {
            $menu->regeneratePreviewToken();
        }
        $this->logChange($request->user()?->id, $menu->id, 'menu_created', ['name' => $menu->name]);

        return redirect()->route('admin.menus.index')->with('success', 'منو ایجاد شد.');
    }

    public function edit(Menu $menu): View
    {
        $pages = Page::published()->orderBy('title')->get(['id', 'title', 'slug']);
        $productCategories = \App\Helpers\SiteHelper::shopEnabled()
            ? ProductCategory::orderBy('order')->get(['id', 'name', 'slug', 'parent_id'])
            : collect();
        $blogCategories = \App\Helpers\SiteHelper::blogEnabled()
            ? Category::orderBy('order')->get(['id', 'name', 'slug'])
            : collect();
        $blogTags = \App\Helpers\SiteHelper::blogEnabled()
            ? Tag::orderBy('name')->get(['id', 'name', 'slug'])
            : collect();
        $itemTypes = MenuItem::types();
        $revisions = $menu->revisions()->limit(15)->get();
        $maxDepth = (int) config('menu.max_depth', 4);
        $allItems = $menu->allItems()->orderBy('order')->get();
        $depthMap = [];
        $byId = $allItems->keyBy('id');
        foreach ($byId as $id => $it) {
            $d = 0;
            $p = $it->parent_id;
            while ($p && isset($byId[$p])) {
                $d++;
                $p = $byId[$p]->parent_id;
            }
            $depthMap[$id] = $d;
        }
        $previewUrl = $menu->preview_token
            ? route('admin.menus.preview', ['menu' => $menu, 'preview_token' => $menu->preview_token])
            : null;

        return view('admin.menus.edit', compact(
            'menu', 'pages', 'productCategories', 'blogCategories', 'blogTags', 'itemTypes', 'revisions', 'maxDepth', 'allItems', 'depthMap', 'previewUrl'
        ));
    }

    /**
     * Menu page picker (Phase D): the "internal_page" destination type
     * already existed end-to-end (MenuItem::resolveUrl(), reference_id
     * validated against exists:pages,id) — the only real gap was a plain
     * <select> dumping every page by title with no search. This backs a
     * real search-as-you-type UI instead of adding any new data model.
     */
    public function searchPages(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $query = Page::published()->orderBy('title');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', '%' . $q . '%')
                    ->orWhere('slug', 'like', '%' . $q . '%');
            });
        }

        return response()->json([
            'pages' => $query->limit(20)->get(['id', 'title', 'slug'])->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
            ]),
        ]);
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $rules = [
            'name' => 'required|string|max:255',
        ];
        if (Menu::menusTableHasStatusColumn()) {
            $rules['status'] = 'required|string|in:draft,published';
        }
        if (Menu::menusTableHasGoLiveAtColumn()) {
            $rules['go_live_at'] = 'nullable|date';
            if ($request->input('go_live_at') === '') {
                $request->merge(['go_live_at' => null]);
            }
        }
        $data = $request->validate($rules);
        if (Menu::menusTableHasGoLiveAtColumn()) {
            if (! empty($data['go_live_at'] ?? null)) {
                $data['go_live_at'] = \Carbon\Carbon::parse($data['go_live_at']);
            } else {
                $data['go_live_at'] = null;
            }
        }
        $menu->update(Menu::stripUnsupportedMenuTableAttributes($data));
        $this->menuService->clearMenuCache($menu->id);
        $this->logChange($request->user()?->id, $menu->id, 'menu_updated', $data);

        return back()->with('success', 'تنظیمات منو ذخیره شد.');
    }

    public function publish(Request $request, Menu $menu): RedirectResponse
    {
        $rules = [
            'revision_note' => 'nullable|string|max:255',
        ];
        if (Menu::menusTableHasGoLiveAtColumn()) {
            $rules['go_live_at'] = 'nullable|date';
            if ($request->input('go_live_at') === '') {
                $request->merge(['go_live_at' => null]);
            }
        }
        $request->validate($rules);
        if (Menu::menusTableHasStatusColumn()) {
            $menu->status = Menu::STATUS_PUBLISHED;
        }
        if (Menu::menusTableHasGoLiveAtColumn()) {
            if ($request->filled('go_live_at')) {
                $menu->go_live_at = $request->date('go_live_at');
            } else {
                $menu->go_live_at = null;
            }
        }
        $menu->save();
        $this->menuService->storeRevision($menu, $request->user()?->id, $request->input('revision_note'));
        $this->menuService->clearMenuCache($menu->id);
        $this->logChange($request->user()?->id, $menu->id, 'menu_published', ['go_live_at' => optional($menu->go_live_at)?->toIso8601String()]);

        return back()->with('success', 'منو منتشر شد و نسخه ذخیره گردید.');
    }

    public function preview(Request $request, Menu $menu): View|RedirectResponse
    {
        $token = (string) $request->query('preview_token', '');
        if ($token === '' || ! hash_equals((string) $menu->preview_token, $token)) {
            abort(403, 'توکن پیش‌نمایش نامعتبر است.');
        }
        $tree = $this->menuService->buildMenuTreeUncached($menu);

        return view('admin.menus.preview', compact('menu', 'tree'));
    }

    public function regeneratePreviewLink(Request $request, Menu $menu): RedirectResponse
    {
        $token = $menu->regeneratePreviewToken();
        $this->logChange($request->user()?->id, $menu->id, 'preview_token_regenerated', []);

        return back()->with('success', 'لینک پیش‌نمایش بازتولید شد.')->with('new_preview_token', $token);
    }

    public function restoreRevision(Request $request, Menu $menu, MenuRevision $revision): RedirectResponse
    {
        if ($revision->menu_id !== $menu->id) {
            abort(404);
        }
        $this->menuService->restoreFromRevision($revision);
        $this->logChange($request->user()?->id, $menu->id, 'revision_restored', ['revision_id' => $revision->id]);

        return back()->with('success', 'نسخهٔ انتخابی بازگردانی شد.');
    }

    public function copyBranch(Request $request, Menu $menu, MenuItem $item): RedirectResponse
    {
        if ($item->menu_id !== $menu->id) {
            abort(404);
        }
        $request->validate([
            'target_parent_id' => 'nullable|exists:menu_items,id',
        ]);
        $targetParentId = $request->input('target_parent_id') ? (int) $request->input('target_parent_id') : null;
        if ($targetParentId) {
            $p = MenuItem::find($targetParentId);
            if (! $p || $p->menu_id !== $menu->id) {
                return back()->with('error', 'والد نامعتبر است.');
            }
        }
        if (! $this->hierarchy->canAttachSubtree($item, $targetParentId, $menu->id)) {
            return back()->with('error', 'امکان کپی در این موقعیت به‌دلیل عمق یا چرخه وجود ندارد.');
        }
        $this->menuService->duplicateBranch($item, $targetParentId);
        $this->menuService->clearMenuCache($menu->id);
        $this->logChange($request->user()?->id, $menu->id, 'branch_copied', ['source_item' => $item->id, 'target_parent' => $targetParentId]);

        return back()->with('success', 'شاخه کپی شد.');
    }

    public function reorderTree(Request $request, Menu $menu): RedirectResponse
    {
        $rawNodes = $request->input('nodes');
        if (is_string($rawNodes)) {
            $decoded = json_decode($rawNodes, true);
            $request->merge(['nodes' => is_array($decoded) ? $decoded : []]);
        }
        $request->validate([
            'nodes' => 'required|array',
            'nodes.*.id' => 'required|integer',
            'nodes.*.parent_id' => 'nullable|integer',
            'nodes.*.order' => 'required|integer|min:0',
        ]);
        $nodes = [];
        foreach ($request->input('nodes', []) as $row) {
            $nodes[] = [
                'id' => (int) $row['id'],
                'parent_id' => isset($row['parent_id']) && $row['parent_id'] !== '' ? (int) $row['parent_id'] : null,
                'order' => (int) $row['order'],
            ];
        }
        $err = $this->hierarchy->validateTreeAssignment($menu->id, $nodes);
        if ($err) {
            return back()->with('error', $err);
        }
        foreach ($nodes as $row) {
            MenuItem::where('id', $row['id'])->where('menu_id', $menu->id)->update([
                'parent_id' => $row['parent_id'],
                'order' => $row['order'],
            ]);
        }
        $this->menuService->clearMenuCache($menu->id);
        $this->logChange($request->user()?->id, $menu->id, 'tree_reordered', ['count' => count($nodes)]);

        return back()->with('success', 'ساختار منو به‌روزرسانی شد.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $id = $menu->id;
        $menu->delete();
        $this->menuService->clearMenuCache($id);

        return redirect()->route('admin.menus.index')->with('success', 'منو حذف شد.');
    }

    public function storeItem(Request $request, Menu $menu): RedirectResponse
    {
        $data = $this->validatedItemPayload($request, false);
        $data['menu_id'] = $menu->id;
        $data['order'] = $menu->allItems()->max('order') + 1;
        $parentId = $data['parent_id'] ?? null;
        if ($parentId) {
            $p = MenuItem::find($parentId);
            if (! $p || $p->menu_id !== $menu->id) {
                return back()->with('error', 'والد باید متعلق به همین منو باشد.');
            }
            $tmp = new MenuItem(array_merge($data, ['id' => 0]));
            if (! $this->hierarchy->canAttachSubtree($tmp, $parentId, $menu->id)) {
                return back()->with('error', 'حداکثر عمق منو رعایت نشد.');
            }
        }
        unset($data['reference_id_page'], $data['reference_id_category'], $data['reference_id_blog_category'], $data['reference_id_blog_tag']);
        $item = MenuItem::create($data);
        $this->menuService->clearMenuCache($menu->id);
        $this->logChange($request->user()?->id, $menu->id, 'item_created', ['item_id' => $item->id]);

        return back()->with('success', 'آیتم اضافه شد.');
    }

    public function updateItem(Request $request, Menu $menu, MenuItem $item): RedirectResponse
    {
        if ($item->menu_id !== $menu->id) {
            abort(404);
        }
        $data = $this->validatedItemPayload($request, true);
        $newParent = $data['parent_id'] ?? null;
        if ($this->hierarchy->wouldCreateCycle($item->id, $newParent, $menu->id)) {
            return back()->with('error', 'نمی‌توان والد چرخه‌ساز تعیین کرد.');
        }
        if ($newParent !== null) {
            $p = MenuItem::find($newParent);
            if (! $p || $p->menu_id !== $menu->id) {
                return back()->with('error', 'والد نامعتبر است.');
            }
            if (! $this->hierarchy->canAttachSubtree($item, $newParent, $menu->id)) {
                return back()->with('error', 'حداکثر عمق منو رعایت نشد.');
            }
        }
        unset($data['reference_id_page'], $data['reference_id_category'], $data['reference_id_blog_category'], $data['reference_id_blog_tag']);
        $item->update($data);
        $this->menuService->clearMenuCache($menu->id);
        $this->logChange($request->user()?->id, $menu->id, 'item_updated', ['item_id' => $item->id]);

        return back()->with('success', 'آیتم به‌روزرسانی شد.');
    }

    public function destroyItem(Menu $menu, MenuItem $item): RedirectResponse
    {
        if ($item->menu_id !== $menu->id) {
            abort(404);
        }
        $itemId = $item->id;
        $item->children()->update(['parent_id' => null]);
        $item->delete();
        $this->menuService->clearMenuCache($menu->id);
        $this->logChange(auth()->id(), $menu->id, 'item_deleted', ['item_id' => $itemId]);

        return back()->with('success', 'آیتم حذف شد.');
    }

    public function reorder(Request $request, Menu $menu): RedirectResponse
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:menu_items,id']);
        foreach ($request->order as $position => $id) {
            MenuItem::where('id', $id)->where('menu_id', $menu->id)->whereNull('parent_id')->update(['order' => $position]);
        }
        $this->menuService->clearMenuCache($menu->id);

        return back()->with('success', 'ترتیب سطح اول به‌روزرسانی شد.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedItemPayload(Request $request, bool $isUpdate): array
    {
        $types = implode(',', [
            MenuItem::TYPE_INTERNAL_PAGE,
            MenuItem::TYPE_BLOG,
            MenuItem::TYPE_SHOP,
            MenuItem::TYPE_CATEGORY,
            MenuItem::TYPE_CUSTOM_URL,
            MenuItem::TYPE_BLOG_CATEGORY,
            MenuItem::TYPE_BLOG_TAG,
        ]);
        $data = $request->validate([
            'parent_id' => 'nullable|exists:menu_items,id',
            'title' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'labels_json' => 'nullable|string',
            'type' => 'required|string|in:'.$types,
            'reference_id' => 'nullable|integer',
            'reference_id_page' => 'nullable|integer',
            'reference_id_category' => 'nullable|integer',
            'reference_id_blog_category' => 'nullable|integer',
            'reference_id_blog_tag' => 'nullable|integer',
            'url' => 'nullable|string|max:1024',
            'utm_query' => 'nullable|string|max:512',
            'utm_json' => 'nullable|string',
            'icon' => 'nullable|string|max:64',
            'open_in_new_tab' => 'nullable|boolean',
            'rel_nofollow' => 'nullable|boolean',
            'css_class' => 'nullable|string|max:128',
            'badge_label' => 'nullable|string|max:64',
            'image_url' => 'nullable|string|max:1024',
            'mega_column' => 'nullable|integer|min:0|max:99',
            'featured_html' => 'nullable|string',
            'auto_list_children' => 'nullable|boolean',
            'visible_modules_json' => 'nullable|string',
        ]);
        $this->validateTypeSpecificFields($request, $isUpdate);
        $labels = null;
        if (! empty($data['labels_json'])) {
            $decoded = json_decode($data['labels_json'], true);
            $labels = is_array($decoded) ? $decoded : null;
        }
        $utmJson = null;
        if (! empty($data['utm_json'])) {
            $decoded = json_decode($data['utm_json'], true);
            $utmJson = is_array($decoded) ? $decoded : null;
        }
        $visibleMods = null;
        if (! empty($data['visible_modules_json'])) {
            $decoded = json_decode($data['visible_modules_json'], true);
            $visibleMods = is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : null;
        }
        $ref = $request->input('reference_id_page')
            ?: $request->input('reference_id_category')
            ?: $request->input('reference_id_blog_category')
            ?: $request->input('reference_id_blog_tag')
            ?: $request->input('reference_id');

        $out = [
            'parent_id' => $data['parent_id'] ?? null,
            'title' => $data['title'],
            'title_en' => $data['title_en'] ?? null,
            'labels' => $labels,
            'type' => $data['type'],
            'reference_id' => $ref ? (int) $ref : null,
            'url' => $this->normalizeUrl((string) ($data['url'] ?? '')),
            'utm_query' => $data['utm_query'] ?? null,
            'utm_json' => $utmJson,
            'icon' => $data['icon'] ?? null,
            'open_in_new_tab' => $request->boolean('open_in_new_tab'),
            'rel_nofollow' => $request->boolean('rel_nofollow'),
            'css_class' => $data['css_class'] ?? null,
            'badge_label' => $data['badge_label'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'mega_column' => $data['mega_column'] ?? null,
            'featured_html' => $data['featured_html'] ?? null,
            'auto_list_children' => $request->boolean('auto_list_children'),
            'visible_modules' => $visibleMods,
        ];
        if ($out['utm_json']) {
            foreach (array_keys($out['utm_json']) as $k) {
                if (! MenuItem::isAllowedUtmKey((string) $k)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'utm_json' => 'کلید UTM فقط به شکل utm_* مجاز است.',
                    ]);
                }
            }
        }

        return $out;
    }

    protected function validateTypeSpecificFields(Request $request, bool $isUpdate): void
    {
        $type = (string) $request->input('type');
        if ($type === MenuItem::TYPE_CUSTOM_URL) {
            $request->validate([
                'url' => ['required', 'string', 'max:1024', 'starts_with:http://,https://,/,#'],
            ]);

            return;
        }

        if ($type === MenuItem::TYPE_INTERNAL_PAGE) {
            $field = $isUpdate ? 'reference_id' : 'reference_id_page';
            $request->validate([$field => 'required|integer|exists:pages,id']);

            return;
        }

        if ($type === MenuItem::TYPE_CATEGORY) {
            $request->validate(['reference_id_category' => 'required|integer|exists:product_categories,id']);
        }

        if ($type === MenuItem::TYPE_BLOG_CATEGORY) {
            $request->validate(['reference_id_blog_category' => 'required|integer|exists:categories,id']);
        }

        if ($type === MenuItem::TYPE_BLOG_TAG) {
            $request->validate(['reference_id_blog_tag' => 'required|integer|exists:tags,id']);
        }
    }

    protected function normalizeUrl(string $url): ?string
    {
        $url = trim($url);

        return $url === '' ? null : $url;
    }

    protected function logChange(?int $userId, int $menuId, string $action, array $diff): void
    {
        MenuChangeLog::create([
            'user_id' => $userId,
            'menu_id' => $menuId,
            'action' => $action,
            'diff_json' => $diff,
        ]);
    }
}
