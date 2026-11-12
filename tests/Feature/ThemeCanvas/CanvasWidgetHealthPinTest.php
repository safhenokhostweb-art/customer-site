<?php

namespace Tests\Feature\ThemeCanvas;

use SaaSPlatform\ThemeRuntime\BlockRenderer as PackageRenderer;
use SaaSPlatform\ThemeRuntime\Providers\ThemeRuntimeServiceProvider;
use Tests\TestCase;

/**
 * Host gate for the saas-platform pin a992d474: WU-3 widget health and the
 * ADR-076 header fixes (1aae4aa7) plus the desktop sticky fix (a992d474).
 * Every assertion needs 1aae4aa7 or later (the sticky one a992d474); each
 * one fails on the previous pin 16c54188.
 */
class CanvasWidgetHealthPinTest extends TestCase
{
    public function test_the_package_ships_the_widget_health_tab(): void
    {
        $this->assertTrue(method_exists(ThemeRuntimeServiceProvider::class, 'canvasHealthPath'));
        $view = view('theme-runtime::canvas.health', ['active' => 'true'])->render();
        $this->assertStringContainsString('root.tbHealthPanel = panel;', $view);

        $audit = (string) file_get_contents(ThemeRuntimeServiceProvider::canvasAuditPath());
        $this->assertStringContainsString('hold: hold,', $audit, 'named holds keep the tab running without Inspect');
        $this->assertStringContainsString('fixTask: fixTask,', $audit);
    }

    public function test_the_package_header_root_has_no_widget_hook_of_its_own(): void
    {
        $html = (new PackageRenderer())->renderNavHeader(['logo_text' => 'برند', 'menu_align' => 'center', 'social_style' => 'brand'], '', 'hdr1');
        $this->assertStringNotContainsString('data-widget-type', $html, 'the host or the wrapper adds the one hook');
        // saas ADR-082 step 2: the header layout is the shared header sheet, not an inline <style>.
        $this->assertStringNotContainsString('<style', $html);
        $css = (string) file_get_contents(\SaaSPlatform\ThemeRuntime\Providers\ThemeRuntimeServiceProvider::cssHeaderPath());
        $this->assertStringContainsString('.tb-nav-header-social:not(.tb-social-icons--brand) .tb-social-icon--eitaa', $css, 'brand style keeps the brand fill');
        $this->assertStringContainsString('.tb-nav-header--menu-center .tb-nav-header-root .tb-nav-header-inner{display:grid', $css, 'menu_align=center keeps its grid');
    }

    public function test_the_desktop_mega_menu_anchor_cannot_override_the_sticky_header(): void
    {
        // a992d474: under theme chrome the block itself is .tb-hdr-sticky; a bare
        // `.tb-nav-header { position: relative }` out-ordered it at >= 1024 px.
        $css = (string) file_get_contents(base_path('vendor/saas-platform/modules-theme-runtime/resources/css/theme-runtime-blocks.css'));
        $this->assertStringContainsString(':where(.tb-nav-header) { position: relative; }', $css);
        $this->assertDoesNotMatchRegularExpression('/(^|[\s,}])\.tb-nav-header\s*\{\s*position/m', $css);
    }
}
