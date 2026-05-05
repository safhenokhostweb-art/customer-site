<?php

namespace Tests\Unit\ThemeBuilder;

use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Phase 3 — Snapshot render testing.
 * Renders elements in isolation and compares HTML to expected snapshot.
 * If structure changes unexpectedly → fail test.
 */
class ElementSnapshotTest extends TestCase
{
    protected BlockRenderer $renderer;

    protected string $snapshotPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new BlockRenderer();
        $this->renderer->setSchemaRegistry(new ElementSchemaRegistry());
        $this->snapshotPath = base_path('tests/fixtures/builder-snapshots');
        if (!File::isDirectory($this->snapshotPath)) {
            File::makeDirectory($this->snapshotPath, 0755, true);
        }
    }

    protected function getSnapshotPath(string $name): string
    {
        return $this->snapshotPath . '/' . $name . '.html';
    }

    protected function assertMatchesSnapshot(string $html, string $snapshotName): void
    {
        $path = $this->getSnapshotPath($snapshotName);
        if (!File::exists($path)) {
            File::put($path, $html);
            $this->assertTrue(true, 'Snapshot created: ' . $snapshotName);
            return;
        }
        $expected = trim(File::get($path));
        $actual = trim($html);
        $this->assertSame($expected, $actual, 'Snapshot mismatch for ' . $snapshotName);
    }

    public function test_heading_snapshot(): void
    {
        $block = ['type' => 'heading', 'id' => 'snap-h', 'props' => ['level' => 2, 'text' => 'عنوان ثابت']];
        $html = $this->renderer->renderBlock($block);
        $this->assertMatchesSnapshot($html, 'heading-default');
    }

    public function test_text_snapshot(): void
    {
        $block = ['type' => 'text', 'id' => 'snap-t', 'props' => ['content' => '<p>متن ثابت</p>']];
        $html = $this->renderer->renderBlock($block);
        $this->assertMatchesSnapshot($html, 'text-default');
    }

    public function test_button_snapshot(): void
    {
        $block = ['type' => 'button', 'id' => 'snap-b', 'props' => ['text' => 'دکمه', 'url' => '#', 'style' => 'primary']];
        $html = $this->renderer->renderBlock($block);
        $this->assertMatchesSnapshot($html, 'button-default');
    }

    public function test_section_with_nested_heading_snapshot(): void
    {
        $block = [
            'type' => 'section',
            'id' => 'snap-s',
            'props' => ['padding' => 'md', 'background' => 'transparent', 'container' => true],
            'blocks' => [
                ['type' => 'row', 'id' => 'r1', 'blocks' => [
                    ['type' => 'column', 'id' => 'c1', 'props' => ['width' => '1/1'], 'blocks' => [
                        ['type' => 'heading', 'id' => 'h1', 'props' => ['text' => 'عنوان']],
                    ]],
                ]],
            ],
        ];
        $html = $this->renderer->renderBlock($block);
        $this->assertMatchesSnapshot($html, 'section-nested-heading');
    }
}