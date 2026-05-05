<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\ProductCategory;
use App\Services\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __construct(
        protected MenuService $menuService
    ) {}

    public function index(): View
    {
        $menus = Menu::orderBy('location')->get();
        $locations = Menu::locations();
        return view('admin.menus.index', compact('menus', 'locations'));
    }

    public function create(): View
    {
        $locations = Menu::locations();
        $existingLocations = Menu::whereNull('site_id')->pluck('location')->toArray();
        return view('admin.menus.create', compact('locations', 'existingLocations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|in:primary,footer,mobile,topbar',
        ]);
        $data['site_id'] = null;
        if (Menu::where('location', $data['location'])->whereNull('site_id')->exists()) {
            return back()->withInput()->with('error', 'یک منو برای این موقعیت از قبل وجود دارد.');
        }
        Menu::create($data);
        return redirect()->route('admin.menus.index')->with('success', 'منو ایجاد شد.');
    }

    public function edit(Menu $menu): View
    {
        $items = $menu->allItems()->get();
        $tree = $this->menuService->buildMenuTree($menu);
        $pages = Page::published()->orderBy('title')->get(['id', 'title', 'slug']);
        $productCategories = \App\Helpers\SiteHelper::shopEnabled()
            ? ProductCategory::orderBy('order')->get(['id', 'name', 'slug'])
            : collect();
        $itemTypes = [
            MenuItem::TYPE_CUSTOM_URL => 'لینک دلخواه',
            MenuItem::TYPE_INTERNAL_PAGE => 'صفحه داخلی',
            MenuItem::TYPE_BLOG => 'وبلاگ',
            MenuItem::TYPE_SHOP => 'فروشگاه',
            MenuItem::TYPE_CATEGORY => 'دسته‌بندی محصول',
        ];
        return view('admin.menus.edit', compact('menu', 'items', 'tree', 'pages', 'productCategories', 'itemTypes'));
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        $menu->update(['name' => $request->name]);
        $this->menuService->clearMenuCache($menu->id);
        return back()->with('success', 'منو به‌روزرسانی شد.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();
        $this->menuService->clearMenuCache($menu->id);
        return redirect()->route('admin.menus.index')->with('success', 'منو حذف شد.');
    }

    public function storeItem(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'parent_id' => 'nullable|exists:menu_items,id',
            'title' => 'required|string|max:255',
            'type' => 'required|in:internal_page,blog,shop,category,custom_url',
            'reference_id' => 'nullable|integer',
            'reference_id_page' => 'nullable|integer',
            'reference_id_category' => 'nullable|integer',
            'url' => 'nullable|string|max:1024',
            'icon' => 'nullable|string|max:64',
            'open_in_new_tab' => 'nullable|boolean',
        ]);
        $data['menu_id'] = $menu->id;
        $data['order'] = $menu->allItems()->max('order') + 1;
        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab');
        $data['reference_id'] = $request->input('reference_id_page') ?: $request->input('reference_id_category') ?: $request->input('reference_id');
        unset($data['reference_id_page'], $data['reference_id_category']);
        MenuItem::create($data);
        $this->menuService->clearMenuCache($menu->id);
        return back()->with('success', 'آیتم اضافه شد.');
    }

    public function updateItem(Request $request, Menu $menu, MenuItem $item): RedirectResponse
    {
        if ($item->menu_id !== $menu->id) {
            abort(404);
        }
        $data = $request->validate([
            'parent_id' => 'nullable|exists:menu_items,id',
            'title' => 'required|string|max:255',
            'type' => 'required|in:internal_page,blog,shop,category,custom_url',
            'reference_id' => 'nullable|integer',
            'url' => 'nullable|string|max:1024',
            'icon' => 'nullable|string|max:64',
            'open_in_new_tab' => 'nullable|boolean',
        ]);
        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab');
        $item->update($data);
        $this->menuService->clearMenuCache($menu->id);
        return back()->with('success', 'آیتم به‌روزرسانی شد.');
    }

    public function destroyItem(Menu $menu, MenuItem $item): RedirectResponse
    {
        if ($item->menu_id !== $menu->id) {
            abort(404);
        }
        $item->children()->update(['parent_id' => null]);
        $item->delete();
        $this->menuService->clearMenuCache($menu->id);
        return back()->with('success', 'آیتم حذف شد.');
    }

    public function reorder(Request $request, Menu $menu): RedirectResponse
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:menu_items,id']);
        foreach ($request->order as $position => $id) {
            MenuItem::where('id', $id)->where('menu_id', $menu->id)->update(['order' => $position]);
        }
        $this->menuService->clearMenuCache($menu->id);
        return back()->with('success', 'ترتیب به‌روزرسانی شد.');
    }
}
