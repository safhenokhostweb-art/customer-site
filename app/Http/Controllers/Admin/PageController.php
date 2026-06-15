<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\BuilderLayoutService;
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

    public function destroy(Page $page)
    {
        if ($page->is_protected) {
            return redirect()->route('admin.pages.index')
                ->with('error', 'این صفحه متعلق به قالب سایت است و قابل حذف نیست. برای حذف، ابتدا قالب را تغییر دهید.');
        }
        $page->delete();
        return redirect()->route('admin.pages.index')->with('success', 'صفحه حذف شد.');
    }
}
