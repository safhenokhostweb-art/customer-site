<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\SiteThemeTemplate;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\ThemeCanvas\SiteThemeTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3D-B — customer-created block id namespace ('cust_') and the
 * companion invariant that ordinary edits, and pre-existing legacy
 * customer ids, are never mutated. Master-origin ids are covered by the
 * saas-platform-side ThemeReleaseSnapshotIdentityTest instead (this repo
 * has no master Theme Builder).
 *
 * The canvas editor's id-generation itself (makeBlock()/_regenIds() in
 * canvas-editor.blade.php) is client-side Alpine.js with no browser test
 * runner in this repo (confirmed absent in the Phase 3A audit) — K/L below
 * are therefore source-level regression guards proving the compiled page
 * emits the intended 'cust_' pattern and not the old 'blk_' pattern at
 * those exact generation points, not a runtime exercise of the JS itself.
 */
class CustomerBlockIdNamespaceTest extends TestCase
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

    private function admin(): User
    {
        License::create([
            'license_key' => 'CUST-ID-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    // ── K/L. new-block and duplicate id generation use the cust_ namespace ─

    public function test_canvas_editor_source_generates_new_block_ids_in_the_customer_namespace(): void
    {
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['home' => ['blocks' => []]]]), 'theme');

        $response = $this->actingAs($this->admin())->get(route('admin.theme-design.canvas-edit'));

        $response->assertOk();
        $response->assertSee("id: 'cust_' + Date.now()", false);
        $response->assertDontSee("id: 'blk_' + Date.now()", false);
    }

    public function test_canvas_editor_source_regenerates_duplicate_ids_in_the_customer_namespace(): void
    {
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['home' => ['blocks' => []]]]), 'theme');

        $response = $this->actingAs($this->admin())->get(route('admin.theme-design.canvas-edit'));

        $response->assertOk();
        $response->assertSee("block.id = 'cust_' + Date.now()", false);
        $response->assertDontSee("block.id = 'blk_' + Date.now()", false);
    }

    // ── K (server-side): reusable-template insertion uses cust_ ──────────

    public function test_inserting_a_saved_template_gives_every_block_a_cust_id(): void
    {
        $template = SiteThemeTemplate::create([
            'name' => 'قالب آزمایشی',
            'type' => 'section',
            'category' => 'hero',
            'blocks' => [
                ['id' => 'blk_original_master_or_customer_id', 'type' => 'heading', 'props' => ['text' => 'x'], 'children' => [
                    ['id' => 'blk_nested_original', 'type' => 'text', 'props' => ['content' => 'y']],
                ]],
            ],
            'is_active' => true,
        ]);

        $blocks = app(SiteThemeTemplateService::class)->insertBlocks($template->id);

        $this->assertStringStartsWith('cust_', $blocks[0]['id']);
        $this->assertStringStartsWith('cust_', $blocks[0]['children'][0]['id']);
        $this->assertNotSame('blk_original_master_or_customer_id', $blocks[0]['id']);
    }

    // ── M. ordinary edits preserve an existing block's id exactly ─────────

    public function test_saving_blocks_preserves_existing_ids_exactly(): void
    {
        $admin = $this->admin();
        Setting::set('active_canvas_theme_data', json_encode([
            'pages' => ['home' => ['blocks' => [
                ['id' => 'blk_master_original_id', 'type' => 'heading', 'props' => ['text' => 'قبل']],
            ]]],
        ]), 'theme');

        $response = $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => 'home',
            'blocks' => [
                ['id' => 'blk_master_original_id', 'type' => 'heading', 'props' => ['text' => 'بعد از ویرایش']],
            ],
        ]);

        $response->assertOk();
        $draft = json_decode(Setting::get('canvas_draft_data'), true);
        $this->assertSame('blk_master_original_id', $draft['pages']['home']['blocks'][0]['id']);
        $this->assertSame('بعد از ویرایش', $draft['pages']['home']['blocks'][0]['props']['text']);
    }

    // ── N. existing legacy (pre-cust_) customer ids are never rewritten ───

    public function test_publishing_never_rewrites_legacy_non_prefixed_customer_ids(): void
    {
        $admin = $this->admin();
        Setting::set('canvas_draft_data', json_encode([
            'pages' => ['home' => ['blocks' => [
                ['id' => 'element-1699999999-ab12', 'type' => 'heading', 'props' => ['text' => 'یک بلوک قدیمی مشتری']],
            ]]],
        ]), 'theme');

        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk();

        $revision = ThemeInstanceRevision::sole();
        $this->assertSame('element-1699999999-ab12', $revision->theme_data['pages']['home']['blocks'][0]['id'], 'a pre-existing legacy customer id must survive publish unchanged — no blanket id migration');
    }

    public function test_rollback_never_rewrites_ids_in_the_restored_snapshot(): void
    {
        $admin = $this->admin();
        Setting::set('canvas_draft_data', json_encode([
            'pages' => ['home' => ['blocks' => [
                ['id' => 'legacy-customer-block-id', 'type' => 'heading', 'props' => ['text' => 'v1']],
            ]]],
        ]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'));
        $firstId = ThemeInstanceRevision::sole()->id;

        Setting::set('canvas_draft_data', json_encode(['pages' => ['home' => ['blocks' => [
            ['id' => 'a-different-block', 'type' => 'text', 'props' => []],
        ]]]]), 'theme');
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'));

        $this->actingAs($admin)
            ->postJson(route('admin.theme-design.canvas-instance-revisions-rollback', $firstId))
            ->assertOk();

        $restored = ThemeInstanceRevision::orderByDesc('revision_number')->first();
        $this->assertSame('legacy-customer-block-id', $restored->theme_data['pages']['home']['blocks'][0]['id']);
    }
}
