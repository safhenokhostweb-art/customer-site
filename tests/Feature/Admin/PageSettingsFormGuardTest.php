<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Builder Commercial Hardening (Phase B), remaining loose end: the legacy
 * admin/pages/edit.blade.php form ("white editor") used to accept `body`
 * edits with zero visible effect once a page's real content lives in
 * builder_content_v2 (Page::usesBuilder() makes the public renderer ignore
 * `body` permanently). That form is demoted to a settings surface — no
 * longer linked as the primary "Edit" action (see PageEditorCanonicalEntryTest
 * for that half) — and both the view and the controller now refuse to
 * present/persist a `body` edit that could never have any effect.
 */
class PageSettingsFormGuardTest extends TestCase
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

    private function builderPage(): Page
    {
        return Page::create([
            'title' => 'صفحه بیلدر',
            'slug' => 'builder-settings-' . uniqid(),
            'builder_type' => Page::BUILDER_TYPE_BUILDER,
            'builder_content_v2' => ['blocks' => [
                ['id' => 'blk_1', 'type' => 'text', 'props' => ['content' => 'محتوای واقعی']],
            ]],
            'body' => 'قدیمی و بی‌اثر',
            'is_published' => true,
            'show_in_menu' => true,
            'order' => 2,
        ]);
    }

    private function legacyPage(): Page
    {
        return Page::create([
            'title' => 'صفحه قدیمی',
            'slug' => 'legacy-settings-' . uniqid(),
            'builder_type' => Page::BUILDER_TYPE_LEGACY,
            'body' => '<p>محتوای اولیه</p>',
            'is_published' => true,
            'show_in_menu' => true,
            'order' => 4,
        ]);
    }

    /**
     * The Builder's own Settings tab already covers every field the legacy
     * form has (title, slug, publish, menu visibility, SEO meta, order,
     * header/footer override) — confirmed by reading builder/edit.blade.php
     * directly, not assumed. It has zero unique responsibility left, so the
     * Pages list's only discoverable edit action is the canonical Builder;
     * the settings route itself stays reachable (compatibility route, same
     * pattern as ADR-054) via the Builder's own page-list "⚙" link and the
     * in-builder notice this phase adds to it — never removed, just no
     * longer offered as a second, competing "Edit" choice on the list.
     */
    public function test_pages_list_offers_only_the_canonical_builder_edit_action(): void
    {
        $page = $this->builderPage();

        $response = $this->actingAs($this->admin())->get(route('admin.pages.index'));

        $response->assertOk();
        $response->assertSee(route('builder.page.edit', $page), false);
        $response->assertDontSee(route('admin.pages.edit', $page), false);
    }

    public function test_settings_form_hides_the_body_editor_for_a_builder_type_page(): void
    {
        $page = $this->builderPage();

        $response = $this->actingAs($this->admin())->get(route('admin.pages.edit', $page));

        $response->assertOk();
        $response->assertDontSee('name="body"', false);
        $response->assertSee(route('builder.page.edit', $page), false);
    }

    public function test_settings_form_still_shows_the_body_editor_for_a_legacy_page(): void
    {
        $page = $this->legacyPage();

        $response = $this->actingAs($this->admin())->get(route('admin.pages.edit', $page));

        $response->assertOk();
        $response->assertSee('name="body"', false);
    }

    public function test_submitting_body_for_a_builder_type_page_is_ignored_but_other_fields_still_save(): void
    {
        $page = $this->builderPage();

        $response = $this->actingAs($this->admin())->put(route('admin.pages.update', $page), [
            'title' => 'صفحه بیلدر',
            'slug' => $page->slug,
            'body' => 'تلاش برای نوشتن محتوای بی‌اثر',
            'order' => 9,
            'is_published' => '1',
            'show_in_menu' => '1',
            'builder_header_override' => '',
            'builder_footer_override' => '',
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $page->refresh();
        $this->assertSame('قدیمی و بی‌اثر', $page->body, 'body must be untouched — this form has no effect on it once the page uses the Builder');
        $this->assertSame(9, $page->order, 'non-content settings fields must still save normally');
        $this->assertSame('محتوای واقعی', $page->builder_content_v2['blocks'][0]['props']['content'], 'the real builder content must be completely unaffected');
    }

    public function test_submitting_body_for_a_legacy_page_still_saves_normally(): void
    {
        $page = $this->legacyPage();

        $response = $this->actingAs($this->admin())->put(route('admin.pages.update', $page), [
            'title' => 'صفحه قدیمی',
            'slug' => $page->slug,
            'body' => '<p>محتوای ویرایش‌شده</p>',
            'order' => 4,
            'is_published' => '1',
            'show_in_menu' => '1',
            'builder_header_override' => '',
            'builder_footer_override' => '',
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $page->refresh();
        $this->assertSame('<p>محتوای ویرایش‌شده</p>', $page->body);
    }
}
