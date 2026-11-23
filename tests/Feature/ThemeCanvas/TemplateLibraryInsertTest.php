<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\ThemeCanvas\EntityLinkCatalog;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SaaSPlatform\ThemeRuntime\TemplateLibrary;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Canvas overhaul Unit 5 (ADR-075): every ready-made library template, on
 * the customer site, goes insert → save into its zone (page, or the
 * virtual __header__/__footer__ pages) → render in the editor's live
 * iframe with no placeholder, its library media present, and every route
 * link resolved by EntityLinkCatalog to the live permalink.
 */
class TemplateLibraryInsertTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    private const ZONE_PAGES = ['page' => 'home', 'header' => '__header__', 'footer' => '__footer__'];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedValidLicense(['shop', 'blog', 'courses']);
        // ADR-087: a licensed solar-industry and safhenokhost site, so "every template"
        // includes the restricted kits. Gating itself is covered by
        // PlatformAssetAccessTest and SafheHeaderAccessTest.
        \App\Models\Setting::set(\App\Services\AssetProvisioning\AssetEntitlementClient::LAST_GOOD_SETTING, json_encode(
            \SaaSPlatform\ThemeRuntime\Access\SiteAssetEntitlements::make('solar_energy', ['solar_industry_kit', 'safhenokhost_exclusive'])->toPayload()
        ), 'platform');
        $this->admin = User::factory()->create(['is_admin' => true]);
        app(ThemeInstanceRevisionService::class)->record([
            'header_blocks' => [], 'footer_blocks' => [], 'header_variants' => [], 'footer_variants' => [],
            'pages' => ['home' => ['slug' => 'home', 'title' => 'خانه', 'is_home' => true, 'blocks' => []]],
        ], ThemeInstanceRevision::TYPE_INSTALL, ['theme_identifier' => 'template-library-' . uniqid()]);
    }

    /** @return list<array> */
    private function flatten(array $blocks): array
    {
        $all = [];
        foreach ($blocks as $block) {
            $all[] = $block;
            $all = array_merge($all, $this->flatten($block['children'] ?? []));
        }
        return $all;
    }

    public function test_every_library_route_is_a_live_destination_in_the_entity_catalog(): void
    {
        $catalog = app(EntityLinkCatalog::class);
        foreach (array_keys(TemplateLibrary::ROUTES) as $route) {
            $this->assertNotSame('', $catalog->resolve(TemplateLibrary::link($route)), "route {$route} resolves");
        }
    }

    public function test_the_modal_lists_the_library_before_saved_templates(): void
    {
        $templates = $this->actingAs($this->admin)->getJson(route('admin.theme-design.canvas.templates'))->assertOk()->json('templates');

        $this->assertSame(array_column(TemplateLibrary::list(), 'id'), array_slice(array_column($templates, 'id'), 0, count(TemplateLibrary::list())));
        $this->assertSame('footer', collect($templates)->firstWhere('id', 'lib:footer-minimal-light')['zone']);
    }

    public function test_every_template_inserts_saves_into_its_zone_and_renders_in_the_live_canvas(): void
    {
        $failures = [];
        foreach (TemplateLibrary::list() as $card) {
            $insert = $this->actingAs($this->admin)->postJson(route('admin.theme-design.canvas.insert-template'), ['template_id' => $card['id']]);
            if ($insert->status() !== 200) {
                $failures[] = "{$card['id']}: insert {$insert->status()} " . $insert->json('message');
                continue;
            }
            $blocks = $insert->json('blocks');
            $pageKey = self::ZONE_PAGES[$insert->json('zone')] ?? null;
            if ($insert->json('zone') !== $card['zone'] || $pageKey === null) {
                $failures[] = "{$card['id']}: zone " . $insert->json('zone');
                continue;
            }
            foreach ($this->flatten($blocks) as $block) {
                if (!str_starts_with($block['id'], 'cust_')) {
                    $failures[] = "{$card['id']}: block id {$block['id']} is not customer-owned";
                }
            }

            $save = $this->actingAs($this->admin)->putJson(route('admin.theme-design.canvas-save-blocks'), ['page_key' => $pageKey, 'blocks' => $blocks]);
            if ($save->status() !== 200 || $save->json('success') !== true) {
                $failures[] = "{$card['id']}: save {$save->status()} " . json_encode($save->json(), JSON_UNESCAPED_UNICODE);
                continue;
            }

            $html = $this->actingAs($this->admin)->get(route('admin.theme-design.canvas-live-preview') . '?page=' . $pageKey)->assertOk()->getContent();
            if (preg_match('/class="[^"]*\btb-placeholder\b/', $html)) {
                $failures[] = "{$card['id']}: live canvas renders a placeholder";
            }
            array_walk_recursive($blocks, function ($value, $key) use (&$failures, $html, $card) {
                if (is_string($value) && str_starts_with($value, '/theme-runtime/library/') && !str_contains($html, parse_url($value, PHP_URL_PATH))) {
                    $failures[] = "{$card['id']}: media {$value} missing from the live canvas";
                }
            });
            foreach ($this->flatten($blocks) as $block) {
                array_walk_recursive($block['props'], function ($value, $key) use (&$failures, $html, $card) {
                    if ($key === 'id' && isset(TemplateLibrary::ROUTES[$value])) {
                        $live = app(EntityLinkCatalog::class)->resolve(TemplateLibrary::link($value));
                        if (!str_contains($html, 'href="' . e($live) . '"')) {
                            $failures[] = "{$card['id']}: route {$value} is not linked to its live permalink {$live}";
                        }
                    }
                });
            }
        }

        $this->assertSame([], array_values(array_unique($failures)));
    }

    public function test_a_header_template_replaces_the_header_and_a_full_page_publishes_with_live_links(): void
    {
        foreach (['lib:header-mega-store' => '__header__', 'lib:page-store-home' => 'home', 'lib:footer-trust-newsletter' => '__footer__'] as $id => $pageKey) {
            $blocks = $this->actingAs($this->admin)->postJson(route('admin.theme-design.canvas.insert-template'), ['template_id' => $id])->json('blocks');
            $this->actingAs($this->admin)->putJson(route('admin.theme-design.canvas-save-blocks'), ['page_key' => $pageKey, 'blocks' => $blocks])->assertOk();
        }
        $this->actingAs($this->admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk()->assertJson(['success' => true]);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\btb-placeholder\b/', $html);
        $this->assertStringContainsString('/theme-runtime/library/mobile-store.webp', $html, 'page media');
        $this->assertStringContainsString('/theme-runtime/library/tech-products.webp', $html, 'mega-menu banner in the header');
        $this->assertStringContainsString('href="' . route('shop.index') . '"', $html);
        $this->assertStringContainsString('href="' . route('blog.index') . '"', $html);
        $this->assertStringContainsString('href="' . route('contact.show') . '"', $html);
    }

    public function test_a_disabled_module_drops_its_links_instead_of_linking_a_dead_page(): void
    {
        \App\Models\License::query()->update(['modules' => []]); // shop, blog and courses off
        \Illuminate\Support\Facades\Cache::flush();
        $blocks = $this->actingAs($this->admin)->postJson(route('admin.theme-design.canvas.insert-template'), ['template_id' => 'lib:header-glass'])->json('blocks');
        $this->actingAs($this->admin)->putJson(route('admin.theme-design.canvas-save-blocks'), ['page_key' => '__header__', 'blocks' => $blocks])->assertOk();

        $html = $this->actingAs($this->admin)->get(route('admin.theme-design.canvas-live-preview') . '?page=__header__')->assertOk()->getContent();
        $this->assertStringContainsString('href="' . route('contact.show') . '"', $html, 'contact is always available');
        $this->assertStringNotContainsString('href="' . url('/shop') . '"', $html);
    }

    public function test_media_is_served_by_the_package_on_the_customer_site(): void
    {
        $response = $this->get(TemplateLibrary::mediaUrl('office-hero.webp'));
        $response->assertOk();
        $this->assertSame('image/webp', $response->headers->get('Content-Type'));
        $this->get('/theme-runtime/library/nope.webp')->assertNotFound();
    }

    public function test_an_unknown_library_id_is_rejected(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.theme-design.canvas.insert-template'), ['template_id' => 'lib:nope'])->assertStatus(422);
    }
}
