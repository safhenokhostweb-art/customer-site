<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\Page;
use App\Services\SeoService;
use App\Services\ThemeCanvas\ThemeRuntimeDocument;

class PageController extends Controller
{
    public function __construct(
        private ThemeRuntimeDocument $runtimeDocument,
        private SeoService $seo,
    ) {}

    public function show(string $slug)
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();
        $pages = Page::published()->menu()->get();
        $viewData = [
            'page' => $page,
            'pages' => $pages,
            'currentPage' => $page,
        ];

        if ($page->usesBuilder()) {
            $blocks = $page->getBuilderContentForRender()['sections'] ?? [];
            $viewData += $this->runtimeDocument
                ->forMode(ThemeRuntimeDocument::MODE_PUBLIC)
                ->buildForPage($page);
            $viewData['seoMeta'] = $this->seo->canvasPage([
                'title' => $page->title,
                'meta_description' => $page->meta_description,
            ], is_array($blocks) ? $blocks : [], (string) $page->title);
        }

        return view(SiteHelper::view('page'), $viewData);
    }
}
