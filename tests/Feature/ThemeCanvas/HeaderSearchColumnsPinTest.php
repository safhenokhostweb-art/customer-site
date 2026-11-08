<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeBuilder\BlockRenderer;
use Tests\TestCase;

/**
 * Host proof for the saas-platform pin 0fcade6a (main), which carries two
 * canonical fixes into this host through the vendor mirror. Fails on the
 * previous pin (a2b3a969).
 *
 * - WU-HEADER-SEARCH-ICON (0fcade6a): with search_mode=icon the storefront
 *   header icon opens only the site search dialog; it used to open the local
 *   popover as well, which stayed open under the dialog. The editor, which
 *   has no search routes, keeps the popover.
 * - columns-honour-ratios (7ef4da83): `columns` lays out one grid track per
 *   declared column, so a lone child no longer fills the whole row.
 */
class HeaderSearchColumnsPinTest extends TestCase
{
    private const ENDPOINTS = ['search' => [
        'all' => ['action' => '/search'],
        'products' => ['action' => '/search', 'params' => ['type' => 'products']],
    ]];

    private const HEADER = ['type' => 'nav_header', 'id' => 'hs', 'props' => ['show_search' => true, 'search_mode' => 'icon']];

    public function test_the_storefront_header_search_icon_opens_only_the_search_dialog(): void
    {
        $site = (new BlockRenderer())->setMode('public')->setContext(['site_endpoints' => self::ENDPOINTS])->renderBlock(self::HEADER);

        $this->assertStringContainsString('<button type="button" class="tb-nav-header-search-toggle tb-nav-header-icon-link" aria-label="باز کردن جستجو" aria-haspopup="dialog" data-tb-search-open="tb-hs-search-form">', $site);
        $this->assertStringNotContainsString('aria-controls="tb-hs-search-form"', $site);
        $this->assertStringContainsString('if(searchToggle.hasAttribute("data-tb-search-open")&&window.TbSiteForms)return;', $site, 'the header script leaves the popover closed');
        $this->assertStringContainsString('class="tb-nav-header-search tb-nav-header-search-popover" role="search" hidden method="get" action="/search"', $site);
        $this->assertStringContainsString('data-tb-block-asset="site_forms"', $site, 'site-forms.js, which owns the dialog, ships with the header');
    }

    public function test_the_editor_header_search_icon_keeps_the_popover_inside_the_preview(): void
    {
        $editor = (new BlockRenderer())->setContext(['editor_mode' => true])->renderBlock(self::HEADER);

        $this->assertStringContainsString('aria-label="باز کردن جستجو" aria-expanded="false" aria-controls="tb-hs-search-form">', $editor);
        $this->assertStringNotContainsString('data-tb-search-open="', $editor);
        // saas ADR-082 step 2: the rule lives in the shared header sheet, its offset a --tb-hdr-* property.
        $css = (string) file_get_contents(\SaaSPlatform\ThemeRuntime\Providers\ThemeRuntimeServiceProvider::cssHeaderPath());
        $this->assertStringContainsString('inset-block-start:calc(100% + var(--tb-hdr-popover-offset, .55rem));inset-inline-end:0;', $css, 'the popover opens into the page, not past its edge');
    }

    public function test_columns_keep_one_track_per_declared_column_for_a_lone_child(): void
    {
        $html = (new BlockRenderer())->setMode('public')->renderBlock([
            'type' => 'columns', 'id' => 'c1', 'props' => ['ratios' => '1/2,1/2'],
            'children' => [['type' => 'text', 'id' => 't1', 'props' => ['content' => 'یک']]],
        ]);

        $this->assertStringContainsString('#tb-c1{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));', $html);
        $this->assertStringNotContainsString('flex:1 1 0', $html);
    }
}
