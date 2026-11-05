<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeBuilder\BlockRenderer;
use Tests\TestCase;

/**
 * WU-1 Inspect Mode + WU-2 Design HUD on the customer host: the customer
 * Canvas editor and its live preview load the package's single include
 * (theme-runtime::canvas.inspect), the editor installs window.TbInspectHost
 * and shows the toggle and the HUD chip, and the preview's widgets carry the
 * data-widget-type hook that Inspect outlines. No local copy of the scripts.
 */
class CanvasInspectHostTest extends TestCase
{
    public function test_the_package_include_ships_inspect_and_the_hud(): void
    {
        $view = view('theme-runtime::canvas.inspect')->render();
        $this->assertStringContainsString('root.TbInspect = api;', $view);
        $this->assertStringContainsString('root.TbAudit = api;', $view);
        $this->assertStringNotContainsString('https://', $view, 'no external origin (CSP)');
    }

    public function test_the_customer_editor_attaches_the_inspect_host(): void
    {
        $editor = (string) file_get_contents(resource_path('views/admin/theme-design/canvas-editor.blade.php'));
        $this->assertSame(1, substr_count($editor, "@include('theme-runtime::canvas.inspect')"));
        $this->assertStringContainsString('window.TbInspect?.attachHost({', $editor);
        $this->assertStringContainsString('frame: () => this.getIframe(),', $editor);
        $this->assertStringContainsString('onAudit: (summary) =>', $editor);
        $this->assertStringContainsString('@click="toggleInspect()" data-tb-inspect-toggle', $editor);
        $this->assertStringContainsString('title="حالت بازرسی (Ctrl+I)"', $editor);
        $this->assertStringContainsString('data-tb-audit-chip', $editor);
    }

    public function test_the_customer_preview_loads_it_and_its_widgets_are_inspectable(): void
    {
        $preview = (string) file_get_contents(resource_path('views/admin/theme-design/canvas-live-preview.blade.php'));
        $this->assertSame(1, substr_count($preview, "@include('theme-runtime::canvas.inspect')"));

        $html = (new BlockRenderer())->setMode('editor')->setContext(['editor_mode' => true])
            ->renderBlock(['type' => 'heading', 'id' => 'h1', 'props' => ['text' => 'سلام']]);
        $this->assertStringContainsString('data-widget-type="heading"', $html);
    }

    public function test_no_local_copy_of_the_scripts(): void
    {
        foreach (['canvas-inspect.js', 'canvas-audit.js'] as $file) {
            $this->assertFileDoesNotExist(resource_path('js/' . $file));
            $this->assertFileDoesNotExist(public_path('js/' . $file));
        }
    }
}
