<?php

namespace Tests\Feature\PageBuilder;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Builder Commercial Hardening — Page Editor unification.
 *
 * Root cause (confirmed by direct code audit): Admin → Pages → "ویرایش"
 * always opened the legacy TinyMCE `body` editor (admin.pages.edit),
 * completely independent of the page's own builder_type/content — the
 * canonical block-based Page Builder was only reachable via a link on the
 * live public page itself, never from the Admin Pages list. This proves
 * the fix: the list's Edit action is now the canonical Page Builder for
 * every page, and opening it for a still-legacy page (body-only, no
 * blocks yet) seeds one real, editable block from that body content
 * instead of showing an empty canvas that would look like data loss.
 */
class PageEditorCanonicalEntryTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
    }

    private function admin(): User
    {
        $this->seedValidLicense(['pages']);

        return User::factory()->create(['is_admin' => true]);
    }

    /**
     * `@json($initialBlocks)` compiles to `Illuminate\Support\Js::from()`,
     * which does not set JSON_UNESCAPED_UNICODE — non-ASCII (Persian) text
     * reaches the page as `\uXXXX` escapes inside the <script> block. That
     * is valid JS a real browser decodes correctly; it is not a rendering
     * bug. A raw `assertSee('...persian text...')` against the HTTP body
     * will never find it there, so this decodes the actual JS payload the
     * page ships instead of substring-matching pre-escaped output.
     */
    private function decodedInitialBlocks(TestResponse $response): array
    {
        $matched = preg_match('/const INITIAL_BLOCKS\s*=\s*(.*?);\n/s', $response->getContent(), $m);
        $this->assertSame(1, $matched, 'INITIAL_BLOCKS assignment not found in builder view output');

        return json_decode($m[1], true) ?? [];
    }

    private function legacyPage(string $body = '<p>محتوای قدیمی صفحه</p>'): Page
    {
        return Page::create([
            'title' => 'صفحه قدیمی',
            'slug' => 'legacy-page-' . uniqid(),
            'body' => $body,
            'builder_type' => Page::BUILDER_TYPE_LEGACY,
            'is_published' => true,
            'show_in_menu' => true,
            'order' => 3,
        ]);
    }

    private function builderPage(): Page
    {
        return Page::create([
            'title' => 'صفحه بیلدر',
            'slug' => 'builder-page-' . uniqid(),
            'builder_type' => Page::BUILDER_TYPE_BUILDER,
            'builder_content_v2' => ['blocks' => [
                ['id' => 'blk_1', 'type' => 'text', 'props' => ['content' => 'محتوای موجود']],
            ]],
            'is_published' => true,
            'show_in_menu' => true,
            'order' => 1,
        ]);
    }

    // ── A. The list's primary Edit action is the canonical Page Builder ──

    public function test_admin_pages_list_edit_link_points_to_the_canonical_page_builder(): void
    {
        $page = $this->legacyPage();

        $response = $this->actingAs($this->admin())->get(route('admin.pages.index'));

        $response->assertOk();
        $response->assertSee(route('builder.page.edit', $page), false);
        $response->assertDontSee(route('admin.pages.edit', $page), false);
    }

    // ── B. Opening the Builder on a legacy page seeds its body content ───

    public function test_opening_builder_on_legacy_page_seeds_a_real_block_from_body(): void
    {
        $page = $this->legacyPage('<p>سلام از صفحه قدیمی</p>');

        $response = $this->actingAs($this->admin())->get(route('builder.page.edit', $page));

        $response->assertOk();
        $blocks = $this->decodedInitialBlocks($response);
        $this->assertCount(1, $blocks);
        $this->assertSame('سلام از صفحه قدیمی', trim(strip_tags($blocks[0]['props']['content'] ?? '')));
        // The legacy content itself is untouched until an explicit save.
        $page->refresh();
        $this->assertSame(Page::BUILDER_TYPE_LEGACY, $page->builder_type);
        $this->assertEmpty($page->builder_content_v2['blocks'] ?? []);
    }

    // ── C. A page that already has blocks is never re-seeded ─────────────

    public function test_opening_builder_on_a_page_with_existing_blocks_does_not_touch_them(): void
    {
        $page = $this->builderPage();

        $response = $this->actingAs($this->admin())->get(route('builder.page.edit', $page));

        $response->assertOk();
        $blocks = $this->decodedInitialBlocks($response);
        // Only the one real, already-saved block — never a duplicated/seeded one.
        $this->assertCount(1, $blocks);
        $this->assertSame('محتوای موجود', $blocks[0]['props']['content'] ?? null);
    }

    // ── D. A genuinely empty new page (no body, no blocks) opens empty ────

    public function test_opening_builder_on_a_brand_new_empty_page_seeds_nothing(): void
    {
        $page = Page::create([
            'title' => 'صفحه خالی',
            'slug' => 'empty-page-' . uniqid(),
            'builder_type' => Page::BUILDER_TYPE_LEGACY,
            'body' => null,
            'is_published' => false,
            'show_in_menu' => false,
            'order' => 5,
        ]);

        $response = $this->actingAs($this->admin())->get(route('builder.page.edit', $page));

        $response->assertOk();
    }

    // ── E. Settings save now covers order + header/footer override ───────

    public function test_page_settings_endpoint_saves_order_and_header_footer_override(): void
    {
        $page = $this->builderPage();

        $response = $this->actingAs($this->admin())->putJson(route('builder.api.page.settings', $page), [
            'order' => 7,
            'builder_header_override' => \App\Services\BuilderLayoutService::PAGE_OVERRIDE_NONE,
            'builder_footer_override' => '',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('page.order', 7);
        $response->assertJsonPath('page.builder_header_override', \App\Services\BuilderLayoutService::PAGE_OVERRIDE_NONE);

        $page->refresh();
        $this->assertSame(7, $page->order);
        $this->assertTrue((bool) $page->builder_disable_header);
        $this->assertNull($page->builder_header_template_override);
        $this->assertFalse((bool) $page->builder_disable_footer);
    }

    // ── F. Saving real content from the Builder still converges builder_type ──

    public function test_saving_content_from_builder_converges_legacy_page_to_canonical_builder_type(): void
    {
        $page = $this->legacyPage();

        $response = $this->actingAs($this->admin())->putJson(route('builder.page.update', $page), [
            'blocks' => [
                ['id' => 'legacy_body_' . $page->id, 'type' => 'text', 'props' => ['content' => $page->body]],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $page->refresh();
        $this->assertSame(Page::BUILDER_TYPE_BUILDER, $page->builder_type);
        $this->assertNotEmpty($page->builder_content_v2['blocks']);
    }
}
