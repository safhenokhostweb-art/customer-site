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
            // TEST-HARNESS-DB-01: PageBuilderController::update() persists
            // a flat top-level `blocks` array (see its own `validate([
            // 'blocks' => 'required|array', ...])`) — the `sections`
            // wrapper this fixture used is an older, since-abandoned
            // content shape the real save endpoint no longer accepts at
            // all (every real save would 422 with "The blocks field is
            // required.").
            'builder_content_v2' => ['blocks' => []],
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

        // TEST-HARNESS-DB-01: same nested section/row/column/widget tree as
        // before, just under the real flat top-level `blocks` key the save
        // endpoint actually validates and persists — see createPage()'s
        // own note.
        $blocks = [
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
        ];

        $this->actingAs($user)->putJson(route('builder.page.update', $page), [
            'blocks' => $blocks,
        ])->assertSuccessful();

        $page->refresh();
        $this->assertNotNull($page->builder_content_v2);
        $this->assertSame('el-s1', $page->builder_content_v2['blocks'][0]['id'] ?? null);
        // FINAL BASELINE CLOSURE: previously a proven, live data-loss bug —
        // PageBuilderController::update() validated with Laravel's flat
        // `blocks.*.id/type/props` rules, which silently dropped every
        // level of nesting below the top block (no rule covered
        // `blocks.*.blocks`). Fixed by validating the raw nested input
        // through the canonical, already-recursive
        // App\Services\ThemeBuilder\ElementSchemaRegistry::validate()
        // instead — see that controller method's own docblock.
        $this->assertSame('عنوان سناریو', $page->builder_content_v2['blocks'][0]['blocks'][0]['blocks'][0]['blocks'][0]['props']['text'] ?? null);

        $forRender = $page->getBuilderContentForRender();
        $this->assertNotEmpty($forRender['sections']);

        $renderer = new BlockRenderer();
        $html = $renderer->setContext([])->renderBlocks($forRender['sections']);
        $this->assertStringContainsString('عنوان سناریو', $html);
        $this->assertStringContainsString('متن سناریو', $html);
    }
}
