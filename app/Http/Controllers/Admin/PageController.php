<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Setting;
use App\Services\BuilderLayoutService;
use App\Support\CanvasReservedSlugs;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function __construct(
        protected BuilderLayoutService $layoutService
    ) {}
    public function index()
    {
        $pages = Page::orderBy('order')->get();
        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_published' => 'nullable|boolean',
            'show_in_menu' => 'nullable|boolean',
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        // A slug the active canvas theme already owns must be rejected —
        // ThemeCanvasPageController::show() resolves the canvas-owned page
        // for that slug first, unconditionally, so a Page row created here
        // would never be publicly reachable with no error or warning.
        if (in_array($data['slug'], CanvasReservedSlugs::all(), true)) {
            return back()->withInput()->withErrors([
                'slug' => 'این آدرس (' . $data['slug'] . ') توسط قالب Canvas فعال استفاده شده و صفحه‌ای با این آدرس در سایت عمومی قابل مشاهده نخواهد بود. آدرس دیگری انتخاب کنید.',
            ]);
        }

        $data['is_published'] = $request->boolean('is_published', true);
        $data['show_in_menu'] = $request->boolean('show_in_menu', true);
        $data['order'] = Page::max('order') + 1;
        Page::create($data);
        return redirect()->route('admin.pages.index')->with('success', 'صفحه ذخیره شد.');
    }

    public function edit(Page $page)
    {
        $headerOptions = $this->layoutService->getHeaderOptionsForPage();
        $footerOptions = $this->layoutService->getFooterOptionsForPage();
        return view('admin.pages.edit', compact('page', 'headerOptions', 'footerOptions'));
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'body' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'order' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'show_in_menu' => 'nullable|boolean',
            'builder_header_override' => 'nullable|string|max:64',
            'builder_footer_override' => 'nullable|string|max:64',
        ]);
        // Same rejection as store(): renaming into a canvas-owned slug
        // would make this page publicly unreachable without any error.
        // Only checked when the slug is actually changing — saving an
        // unrelated field on a page whose slug already happened to match
        // (a pre-existing state) must not be blocked here.
        if ($data['slug'] !== $page->slug && in_array($data['slug'], CanvasReservedSlugs::all(), true)) {
            return back()->withInput()->withErrors([
                'slug' => 'این آدرس (' . $data['slug'] . ') توسط قالب Canvas فعال استفاده شده و صفحه‌ای با این آدرس در سایت عمومی قابل مشاهده نخواهد بود. آدرس دیگری انتخاب کنید.',
            ]);
        }

        $data['is_published'] = $request->boolean('is_published');
        $data['show_in_menu'] = $request->boolean('show_in_menu');

        if ($data['builder_header_override'] === BuilderLayoutService::PAGE_OVERRIDE_NONE) {
            $data['builder_disable_header'] = true;
            $data['builder_header_template_override'] = null;
        } else {
            $data['builder_disable_header'] = false;
            $data['builder_header_template_override'] = $data['builder_header_override'] ?: null;
        }
        if ($data['builder_footer_override'] === BuilderLayoutService::PAGE_OVERRIDE_NONE) {
            $data['builder_disable_footer'] = true;
            $data['builder_footer_template_override'] = null;
        } else {
            $data['builder_disable_footer'] = false;
            $data['builder_footer_template_override'] = $data['builder_footer_override'] ?: null;
        }
        unset($data['builder_header_override'], $data['builder_footer_override']);
        $page->update($data);
        return redirect()->route('admin.pages.index')->with('success', 'صفحه به‌روزرسانی شد.');
    }

    public function bulkStatus(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:pages,id',
            'status' => 'required|in:published,draft',
        ]);

        $isPublished = $request->status === 'published';
        Page::whereIn('id', $request->ids)->update(['is_published' => $isPublished]);

        $label = $isPublished ? 'منتشر شده' : 'پیش‌نویس';
        return back()->with('success', count($request->ids) . ' صفحه به وضعیت «' . $label . '» تغییر یافت.');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:pages,id',
        ]);

        $activeThemeId = Setting::get('active_canvas_theme_id', '');
        $pages = Page::whereIn('id', $request->ids)->get();

        $deleted  = 0;
        $skipped  = 0;

        foreach ($pages as $page) {
            if ($page->is_protected && !empty($activeThemeId) && $page->theme_id === $activeThemeId) {
                $skipped++;
                continue;
            }
            if ($page->is_protected) {
                $page->update(['is_protected' => false, 'theme_id' => null, 'theme_page_key' => null]);
            }
            $page->delete();
            $deleted++;
        }

        $msg = $deleted . ' صفحه حذف شد.';
        if ($skipped) {
            $msg .= ' ' . $skipped . ' صفحه متعلق به قالب فعال بود و حذف نشد.';
        }
        return back()->with($skipped && !$deleted ? 'error' : 'success', $msg);
    }

    public function destroy(Page $page)
    {
        if ($page->is_protected) {
            $activeThemeId = Setting::get('active_canvas_theme_id', '');

            // If the page's theme is no longer the active theme (theme was deleted/changed),
            // auto-unprotect and allow deletion instead of blocking the user.
            if (empty($activeThemeId) || $page->theme_id !== $activeThemeId) {
                $page->update(['is_protected' => false, 'theme_id' => null, 'theme_page_key' => null]);
            } else {
                return redirect()->route('admin.pages.index')
                    ->with('error', 'این صفحه متعلق به قالب سایت است و قابل حذف نیست. برای حذف، ابتدا قالب را تغییر دهید.');
            }
        }
        $page->delete();
        return redirect()->route('admin.pages.index')->with('success', 'صفحه حذف شد.');
    }
}
