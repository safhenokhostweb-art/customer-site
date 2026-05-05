<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Services\ThemeBuilder\BlockRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

/**
 * Phase 5 — Scenario testing.
 * Add section → add column → add widget → save → reload → verify integrity.
 */
class BuilderScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    protected function createPage(): Page
    {
        return Page::create([
            'title' => 'صفحه تست سناریو',
            'slug' => 'test-scenario-' . uniqid(),
            'body' => '',
            'builder_content_v2' => ['sections' => []],
            'builder_type' => Page::BUILDER_TYPE_BUILDER,
            'is_published' => false,
            'show_in_menu' => false,
        ]);
    }

    protected function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->update(['is_admin' => true]);
        return $user;
    }

    public function test_save_builder_content_and_reload_preserves_integrity(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $page = $this->createPage();
        $user = $this->createAdmin();

        $content = [
            'sections' => [
                [
                    'id' => 'el-s1',
                    'type' => 'section',
                    'props' => ['padding' => 'md', 'background' => 'transparent', 'container' => true],
                    'blocks' => [
                        [
                            'id' => 'el-r1',
                            'type' => 'row',
                            'blocks' => [
                                [
                                    'id' => 'el-c1',
                                    'type' => 'column',
                                    'props' => ['width' => '1/2'],
                                    'blocks' => [
                                        ['id' => 'el-h1', 'type' => 'heading', 'props' => ['text' => 'عنوان سناریو']],
                                    ],
                                ],
                                [
                                    'id' => 'el-c2',
                                    'type' => 'column',
                                    'props' => ['width' => '1/2'],
                                    'blocks' => [
                                        ['id' => 'el-t1', 'type' => 'text', 'props' => ['content' => 'متن سناریو']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->actingAs($user)->putJson(route('builder.page.update', $page), [
            'builder_content_v2' => $content,
        ])->assertSuccessful();

        $page->refresh();
        $this->assertNotNull($page->builder_content_v2);
        $this->assertSame('el-s1', $page->builder_content_v2['sections'][0]['id'] ?? null);
        $this->assertSame('عنوان سناریو', $page->builder_content_v2['sections'][0]['blocks'][0]['blocks'][0]['blocks'][0]['props']['text'] ?? null);

        $forRender = $page->getBuilderContentForRender();
        $this->assertNotEmpty($forRender['sections']);

        $renderer = new BlockRenderer();
        $html = $renderer->setContext([])->renderBlocks($forRender['sections']);
        $this->assertStringContainsString('عنوان سناریو', $html);
        $this->assertStringContainsString('متن سناریو', $html);
    }
}
