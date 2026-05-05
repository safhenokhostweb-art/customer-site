<?php

namespace Tests\Unit\ThemeBuilder;

use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use Tests\TestCase;

/**
 * Phase 4 — Builder structure stress test.
 */
class BuilderStressTest extends TestCase
{
    protected BlockRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new BlockRenderer();
        $this->renderer->setSchemaRegistry(new ElementSchemaRegistry());
    }

    protected function buildStressSections(int $count): array
    {
        $sections = [];
        for ($i = 0; $i < $count; $i++) {
            $sections[] = [
                'id' => 'stress-sec-' . $i,
                'type' => 'section',
                'props' => ['padding' => 'md', 'background' => 'transparent', 'container' => true],
                'blocks' => [
                    [
                        'id' => 'stress-row-' . $i,
                        'type' => 'row',
                        'blocks' => [
                            [
                                'id' => 'stress-col-' . $i . '-1',
                                'type' => 'column',
                                'props' => ['width' => '1/2'],
                                'blocks' => [
                                    ['id' => 'stress-h-' . $i . '-1', 'type' => 'heading', 'props' => ['text' => 'بخش ' . ($i + 1) . ' چپ']],
                                    ['id' => 'stress-t-' . $i . '-1', 'type' => 'text', 'props' => ['content' => 'متن']],
                                ],
                            ],
                            [
                                'id' => 'stress-col-' . $i . '-2',
                                'type' => 'column',
                                'props' => ['width' => '1/2'],
                                'blocks' => [
                                    ['id' => 'stress-h-' . $i . '-2', 'type' => 'heading', 'props' => ['text' => 'بخش ' . ($i + 1) . ' راست']],
                                    ['id' => 'stress-btn-' . $i, 'type' => 'button', 'props' => ['text' => 'دکمه', 'url' => '#']],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
        return $sections;
    }

    public function test_render_50_sections_completes_without_exception(): void
    {
        $sections = $this->buildStressSections(50);
        $start = microtime(true);
        $html = $this->renderer->renderBlocks($sections);
        $elapsed = microtime(true) - $start;

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('tb-section', $html);
        $this->assertStringContainsString('بخش 1 چپ', $html);
        $this->assertStringContainsString('بخش 50 راست', $html);
        $this->assertLessThan(5.0, $elapsed, 'Render 50 sections within 5 seconds');
    }

    public function test_render_deeply_nested_structure(): void
    {
        $section = [
            'id' => 'deep-1',
            'type' => 'section',
            'props' => [],
            'blocks' => [
                [
                    'id' => 'r1',
                    'type' => 'row',
                    'blocks' => [
                        [
                            'id' => 'c1',
                            'type' => 'column',
                            'props' => ['width' => '1/1'],
                            'blocks' => [
                                [
                                    'id' => 'inner-sec',
                                    'type' => 'section',
                                    'props' => [],
                                    'blocks' => [
                                        [
                                            'id' => 'r2',
                                            'type' => 'row',
                                            'blocks' => [
                                                [
                                                    'id' => 'c2',
                                                    'type' => 'column',
                                                    'props' => ['width' => '1/2'],
                                                    'blocks' => [
                                                        ['id' => 'h', 'type' => 'heading', 'props' => ['text' => 'عمیق']],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $html = $this->renderer->renderBlocks([$section]);
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('عمیق', $html);
    }
}
