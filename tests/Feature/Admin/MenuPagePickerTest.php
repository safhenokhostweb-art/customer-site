<?php

namespace Tests\Feature\Admin;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Phase D — menu item "existing page" destination. The backend contract
 * (MenuItem::TYPE_INTERNAL_PAGE + reference_id -> resolveUrl()) already
 * existed; the actual product gap was the authoring UI: a plain <select>
 * dumping every page by title, with no search. This proves the new search
 * endpoint (backing the searchable picker) and the full save -> reload ->
 * public-render round trip for a menu item pointed at an existing page.
 */
class MenuPagePickerTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
    }

    private function admin(): User
    {
        $this->seedValidLicense(['menu']);

        return User::factory()->create(['is_admin' => true]);
    }

    private function page(string $title, string $slug): Page
    {
        return Page::create([
            'title' => $title,
            'slug' => $slug,
            'body' => '<p>x</p>',
            'is_published' => true,
        ]);
    }

    public function test_search_endpoint_matches_by_title(): void
    {
        $this->page('درباره ما', 'about-us');
        $this->page('تماس با ما', 'contact-us');

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.menus.pages.search', ['q' => 'درباره']));

        $response->assertOk();
        $titles = collect($response->json('pages'))->pluck('title');
        $this->assertTrue($titles->contains('درباره ما'));
        $this->assertFalse($titles->contains('تماس با ما'));
    }

    public function test_search_endpoint_matches_by_slug(): void
    {
        $this->page('خدمات', 'our-services');

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.menus.pages.search', ['q' => 'our-serv']));

        $response->assertOk();
        $this->assertCount(1, $response->json('pages'));
    }

    public function test_search_endpoint_excludes_unpublished_pages(): void
    {
        Page::create(['title' => 'پیش‌نویس مخفی', 'slug' => 'hidden-draft', 'body' => '<p>x</p>', 'is_published' => false]);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.menus.pages.search', ['q' => 'پیش‌نویس']));

        $response->assertOk();
        $this->assertCount(0, $response->json('pages'));
    }

    public function test_search_endpoint_with_empty_query_returns_a_bounded_list_not_an_error(): void
    {
        $this->page('صفحه یک', 'page-one');

        $response = $this->actingAs($this->admin())->getJson(route('admin.menus.pages.search'));

        $response->assertOk();
        $this->assertIsArray($response->json('pages'));
    }

    public function test_creating_a_menu_item_pointed_at_an_existing_page_resolves_to_its_real_url(): void
    {
        $page = $this->page('راهنما', 'guide');
        $admin = $this->admin();
        $menu = Menu::create(['name' => 'اصلی', 'location' => 'primary', 'site_id' => null]);

        $response = $this->actingAs($admin)->post(route('admin.menus.items.store', $menu), [
            'title' => 'راهنما',
            'type' => MenuItem::TYPE_INTERNAL_PAGE,
            'reference_id_page' => $page->id,
        ]);

        $response->assertRedirect();
        $item = MenuItem::where('menu_id', $menu->id)->where('title', 'راهنما')->firstOrFail();
        $this->assertSame(MenuItem::TYPE_INTERNAL_PAGE, $item->type);
        $this->assertSame($page->id, $item->reference_id);
        $this->assertSame(route('page.show', $page->slug), $item->resolveUrl());
    }

    public function test_updating_a_menu_item_to_point_at_a_different_page_changes_its_resolved_url(): void
    {
        $pageA = $this->page('صفحه الف', 'page-a');
        $pageB = $this->page('صفحه ب', 'page-b');
        $admin = $this->admin();
        $menu = Menu::create(['name' => 'اصلی', 'location' => 'primary', 'site_id' => null]);
        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'لینک',
            'type' => MenuItem::TYPE_INTERNAL_PAGE,
            'reference_id' => $pageA->id,
            'order' => 0,
        ]);

        $this->assertSame(route('page.show', $pageA->slug), $item->fresh()->resolveUrl());

        $response = $this->actingAs($admin)->put(route('admin.menus.items.update', [$menu, $item]), [
            'title' => 'لینک',
            'type' => MenuItem::TYPE_INTERNAL_PAGE,
            'reference_id' => $pageB->id,
        ]);

        $response->assertRedirect();
        $this->assertSame(route('page.show', $pageB->slug), $item->fresh()->resolveUrl());
    }

    public function test_menu_edit_page_renders_the_picker_and_not_a_raw_select_of_every_page(): void
    {
        $this->page('صفحه نمایشی', 'demo-page');
        $admin = $this->admin();
        $menu = Menu::create(['name' => 'اصلی', 'location' => 'primary', 'site_id' => null]);

        $response = $this->actingAs($admin)->get(route('admin.menus.edit', $menu));

        $response->assertOk();
        $response->assertSee('js-page-picker', false);
        $response->assertSee('page-picker-input', false);
        $response->assertDontSee('<select name="reference_id_page">', false);
    }
}
