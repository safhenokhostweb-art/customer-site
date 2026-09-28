<?php

namespace App\Services\ThemeCanvas;

use App\Helpers\SiteHelper;
use App\Models\Page;
use App\Services\BuilderLayoutService;
use App\Services\MenuService;
use App\Services\ThemeCanvas\DataBinding\ThemeDataBindingRegistry;
use App\Services\ThemeCanvas\DataBinding\DynamicApiDataSource;
use SaaSPlatform\ThemeRuntime\Layout\DirectionCompatibilityAdapter;
use SaaSPlatform\ThemeRuntime\Layout\DirectionProjector;
use SaaSPlatform\ThemeRuntime\DataBinding\DataFetcher;
use SaaSPlatform\ThemeRuntime\DataBinding\DataFetchResult;
use SaaSPlatform\ThemeRuntime\DataBinding\DataSourceConfig;

/** Builds the full Canvas Theme document for public and editor runtimes. */
class ThemeRuntimeDocument
{
    public const MODE_EDITOR = 'editor';

    public const MODE_PUBLIC = 'public';

    private string $mode;

    public function __construct(
        private ThemeCanvasRenderer $renderer,
        private MenuService $menuService,
        private ThemeAssetLocalizationService $assetLocalizer,
        private BuilderLayoutService $layoutService,
        private ThemeDataBindingRegistry $dataBindings,
        private DynamicApiDataSource $dynamicApi,
        string $mode = self::MODE_EDITOR,
    ) {
        $this->mode = self::validateMode($mode);
    }

    public function forMode(string $mode): self
    {
        $document = clone $this;
        $document->mode = self::validateMode($mode);

        return $document;
    }

    public function __clone(): void
    {
        // Renderer state (mode, context, collected assets and memoized media)
        // is document-local. A public projection must never mutate the
        // request's editor-mode document resolved from the container.
        $this->renderer = clone $this->renderer;
    }

    /** @return array<string, mixed> */
    public function buildForPage(Page $page): array
    {
        $content = $page->getBuilderContentForRender();
        $blocks = is_array($content['sections'] ?? null) ? $content['sections'] : [];

        return $this->build(
            $this->layoutService->currentThemeData(),
            $blocks,
            (string) $page->title,
            (string) $page->slug,
            layoutPage: $page,
            renderChrome: false,
        );
    }

    /** @return array<string, mixed> */
    public function build(
        array $themeData,
        array $blocks,
        string $title,
        string $pageKey,
        bool $editorPreview = false,
        ?string $editorRegion = null,
        ?Page $layoutPage = null,
        bool $renderChrome = true,
    ): array {
        $editorPreview = $this->mode === self::MODE_EDITOR && $editorPreview;
        $headerBlocks = $renderChrome && $editorRegion === null
            ? $this->layoutService->getHeaderBlocks($layoutPage, $themeData)
            : [];
        $footerBlocks = $renderChrome && $editorRegion === null
            ? $this->layoutService->getFooterBlocks($layoutPage, $themeData)
            : [];

        $resolvedBindings = $this->dataBindings->resolveBlockLists($headerBlocks, $footerBlocks, $blocks);
        $rendererContext = $this->prepareRenderer($themeData, false, $resolvedBindings);
        $this->renderer->preloadMediaFor($headerBlocks, $footerBlocks, $blocks);
        $headerHtml = $this->renderer->renderBlocks($headerBlocks);
        $footerHtml = $this->renderer->renderBlocks($footerBlocks);

        if ($editorPreview) {
            $this->renderer->setContext(array_replace($rendererContext, ['editor_mode' => true]));
            $contentHtml = $this->renderEditableBlocks($blocks);
        } else {
            $contentHtml = $this->renderer->renderBlocks($blocks);
        }

        $extensionAssets = $this->renderer->getCollectedAssets();
        $runtime = $this->resolveCapabilities($themeData);
        $themeAssets = $this->assetLocalizer->runtimeAssets($themeData['theme_assets'] ?? []);

        if (($themeAssets['files'] ?? []) !== []) {
            $headerHtml = $this->rewriteDeclaredAssets($headerHtml, $themeAssets['files']);
            $footerHtml = $this->rewriteDeclaredAssets($footerHtml, $themeAssets['files']);
            $contentHtml = $this->rewriteDeclaredAssets($contentHtml, $themeAssets['files']);
        }

        return [
            'headerHtml' => $headerHtml,
            'footerHtml' => $footerHtml,
            'contentHtml' => $contentHtml,
            'themeData' => $themeData,
            'pageTitle' => $title,
            'globalStyles' => $themeData['global_styles'] ?? [],
            'extensionAssets' => $extensionAssets,
            'themeAssets' => $themeAssets,
            'canvasPageKey' => $pageKey,
            'chromeOwner' => $editorRegion === null ? $runtime['chromeOwner'] : 'theme',
            'documentDirection' => $runtime['documentDirection'],
            'documentLang' => $runtime['documentLang'],
            'platformPresentationCss' => $runtime['platformPresentationCss'],
            'canvasEditorPreview' => $editorPreview,
            'showBuilderEditBar' => !$editorPreview,
            'themeRuntimeMode' => $this->mode,
            'capabilityProjection' => $this->capabilityProjection($editorPreview),
        ];
    }

    /** @param array<string,array<string,mixed>> $resolvedBindings */
    private function prepareRenderer(array $themeData, bool $editorMode, array $resolvedBindings = []): array
    {
        $styles = is_array($themeData['global_styles'] ?? null) ? $themeData['global_styles'] : [];
        $context = [
            'editor_mode' => $editorMode,
            'runtime_mode' => $this->mode,
            'locale' => (string) ($styles['locale'] ?? app()->getLocale()),
            'menu_html' => $this->menuService->renderMenuHtml('primary', admin: false),
            'footer_menu_html' => $this->menuService->renderMenuHtml('footer', admin: false),
            'site_name' => SiteHelper::siteName(),
            'site_logo_html' => view('components.site-brand', ['class' => 'tb-nav-header-logo'])->render(),
            'theme_bindings' => $resolvedBindings,
            'data_fetcher' => new DataFetcher(
                apiResolver: fn (DataSourceConfig $source): array => $this->dynamicApi->fetch($source),
                internalResolver: function (DataSourceConfig $source): DataFetchResult {
                    // Milestone 2 exposes only the explicitly contracted
                    // product source; other internal identifiers fail closed.
                    if ($source->endpointUrl !== 'products') {
                        return DataFetchResult::error();
                    }
                    $resolved = $this->dataBindings->resolveDynamicSource(
                        $source->endpointUrl,
                        $source->queryParams,
                    );
                    if (($resolved['status'] ?? null) !== 'resolved') {
                        return DataFetchResult::error();
                    }
                    $items = array_map(static fn (array $item): array => [
                        'title' => (string) ($item['title'] ?? ''),
                        'url' => (string) ($item['url'] ?? '#'),
                        'image' => (string) ($item['image_url'] ?? ''),
                        'image_alt' => (string) ($item['title'] ?? ''),
                        'price' => (string) ($item['price_html'] ?? ''),
                        'excerpt' => (string) ($item['category'] ?? ''),
                    ], array_values(array_filter($resolved['items'] ?? [], 'is_array')));

                    return DataFetchResult::success($items);
                },
            ),
        ];
        $this->renderer->setMode($this->mode);
        $this->renderer->setThemeData($themeData);
        $this->renderer->setContext($context);

        return $context;
    }

    /** @return array<string, list<string>> */
    private function capabilityProjection(bool $editorPreview): array
    {
        $runtime = [
            'theme-runtime::canvas.runtime',
            'theme-runtime::canvas.identity',
            'theme-runtime::interaction.runtime',
        ];

        if ($this->mode === self::MODE_PUBLIC) {
            return ['runtime' => $runtime];
        }

        return [
            'runtime' => $runtime,
            'editor' => $editorPreview
                ? ['selection', 'inline-editing', 'drag-drop', 'structural-services']
                : [],
        ];
    }

    private static function validateMode(string $mode): string
    {
        if (! in_array($mode, [self::MODE_EDITOR, self::MODE_PUBLIC], true)) {
            throw new \InvalidArgumentException("Unsupported Theme runtime mode [{$mode}].");
        }

        return $mode;
    }

    private function renderEditableBlocks(array $blocks): string
    {
        if ($blocks === []) {
            return '<div data-testid="tb-canvas-empty" style="display:flex;align-items:center;justify-content:center;min-height:80vh;color:#9ca3af;padding:2rem;">No blocks</div>';
        }

        $html = '';
        foreach (array_values($blocks) as $index => $block) {
            $html .= $this->renderer->renderBlock($block);
        }

        return $html;
    }

    /** @return array{chromeOwner:string,documentDirection:string,documentLang:string,platformPresentationCss:bool} */
    private function resolveCapabilities(array $themeData): array
    {
        $styles = is_array($themeData['global_styles'] ?? null) ? $themeData['global_styles'] : [];
        $locale = (string) ($styles['locale'] ?? app()->getLocale());
        $canonicalDocument = is_array($themeData['document'] ?? null)
            ? $themeData['document']
            : (is_array($themeData['layout'] ?? null) ? $themeData['layout'] : []);
        $projection = DirectionCompatibilityAdapter::resolve($canonicalDocument, $styles, $locale);
        $attributes = DirectionProjector::attributesWithDefault($projection);
        $language = strtolower((string) strtok(str_replace('_', '-', (string) $attributes['lang']), '-'));

        return [
            'chromeOwner' => ($styles['chrome_owner'] ?? null) === 'theme' ? 'theme' : 'platform',
            'documentDirection' => $attributes['dir'],
            'documentLang' => in_array($language, ['fa', 'ar', 'en'], true) ? $language : 'fa',
            'platformPresentationCss' => ($styles['presentation_css_owner'] ?? null) !== 'theme',
        ];
    }

    /** @param array<string, string> $files */
    private function rewriteDeclaredAssets(string $html, array $files): string
    {
        $resolve = static function (string $reference) use ($files): ?string {
            $candidate = trim(strip_tags($reference));

            return $files[$candidate] ?? null;
        };

        $html = preg_replace_callback(
            '/src="([^"]+)"/',
            static fn (array $match) => ($url = $resolve($match[1])) !== null ? 'src="'.$url.'"' : $match[0],
            $html,
        ) ?? $html;

        return preg_replace_callback(
            '/url\(([\'\"]?)([^\'\")]+)\1\)/',
            static fn (array $match) => ($url = $resolve($match[2])) !== null ? 'url('.$match[1].$url.$match[1].')' : $match[0],
            $html,
        ) ?? $html;
    }
}
