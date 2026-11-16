<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\AssetProvisioning\AssetEntitlementClient;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SaaSPlatform\ThemeRuntime\Access\SiteAssetEntitlements;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * ADR-087 on the tenant: the editor syncs its asset entitlements from the
 * platform (GET /api/asset-entitlements, licence-key auth), keeps the last
 * good answer, and gates the template library, the palette and saving by
 * it. The platform side is faked at the HTTP boundary only.
 */
class PlatformAssetAccessTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $license = $this->seedValidLicense(['shop', 'blog']);
        $license->forceFill(['license_key' => 'TEST-KEY-ADR087-0001'])->save();
        config(['site.platform_url' => 'https://platform.test']);
        Cache::forget(AssetEntitlementClient::CACHE_KEY);
        $this->admin = User::factory()->create(['is_admin' => true]);
        app(ThemeInstanceRevisionService::class)->record([
            'header_blocks' => [], 'footer_blocks' => [], 'header_variants' => [], 'footer_variants' => [],
            'pages' => ['home' => ['slug' => 'home', 'title' => 'خانه', 'is_home' => true, 'blocks' => []]],
        ], ThemeInstanceRevision::TYPE_INSTALL, ['theme_identifier' => 'asset-access-' . uniqid()]);
    }

    /** @var array{0: array, 1: int} the platform's current answer */
    private array $answer = [['available' => false], 503];

    private bool $faked = false;

    private function platformAnswers(?array $payload, int $status = 200): void
    {
        $this->answer = [$payload === null ? ['available' => false] : ['available' => true] + $payload, $status];
        if (!$this->faked) {
            // One stub reading the mutable answer: a second Http::fake() for
            // the same pattern would never replace the first.
            Http::fake(['platform.test/api/asset-entitlements*' => fn () => Http::response($this->answer[0], $this->answer[1])]);
            $this->faked = true;
        }
    }

    private function payload(?string $vertical, array $keys = [], array $grants = []): array
    {
        return json_decode(json_encode(SiteAssetEntitlements::make($vertical, $keys, $grants)->toPayload()), true);
    }

    private function templateIds(): array
    {
        return array_column($this->actingAs($this->admin)->getJson(route('admin.theme-design.canvas.templates'))->assertOk()->json('templates'), 'id');
    }

    public function test_the_client_authenticates_with_the_licence_and_stores_the_last_good_answer(): void
    {
        $this->platformAnswers($this->payload('solar_energy', ['solar_industry_kit']));

        $site = app(AssetEntitlementClient::class)->refresh();

        $this->assertSame('solar_energy', $site->vertical);
        $this->assertContains('solar_industry_kit', $site->entitlementKeys);
        $this->assertContains('shop', $site->entitlementKeys, 'the licence projection is unioned in');
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer TEST-KEY-ADR087-0001')
            && str_contains($request->url(), '/api/asset-entitlements'));
        $this->assertNotNull(Setting::get(AssetEntitlementClient::LAST_GOOD_SETTING));
    }

    public function test_an_outage_or_a_malformed_answer_keeps_the_last_good_answer(): void
    {
        $this->platformAnswers($this->payload('solar_energy', ['solar_industry_kit']));
        app(AssetEntitlementClient::class)->refresh();
        Cache::forget(AssetEntitlementClient::CACHE_KEY);

        foreach ([[['schema' => 9], 200], [null, 500], [['schema' => 1, 'vertical' => 'x', 'entitlement_keys' => 'all', 'grants' => []], 200]] as [$bad, $status]) {
            $this->platformAnswers($bad === null ? null : $bad, $status);
            $this->app->forgetScopedInstances();
            $site = app(AssetEntitlementClient::class)->refresh();
            $this->assertSame('solar_energy', $site->vertical);
            $this->assertContains('solar_industry_kit', $site->entitlementKeys);
        }
    }

    public function test_a_site_that_never_synced_holds_public_assets_only(): void
    {
        $this->platformAnswers(null, 503);

        $ids = $this->templateIds();

        $this->assertNotContains('lib:solar-header', $ids);
        $this->assertNotContains('lib:solar-page-home', $ids);
        $this->assertContains('lib:header-glass', $ids);
        $this->actingAs($this->admin)->postJson(route('admin.theme-design.canvas.insert-template'), ['template_id' => 'lib:solar-header'])
            ->assertStatus(403)->assertJsonPath('success', false);
        $this->actingAs($this->admin)->postJson(route('admin.theme-design.canvas.insert-template'), ['template_id' => 'lib:header-glass'])
            ->assertOk();
    }

    public function test_inside_the_vertical_without_the_kit_the_header_is_a_locked_purchase_teaser(): void
    {
        $this->platformAnswers($this->payload('solar_energy'));
        $this->actingAs($this->admin)->get(route('admin.theme-design.canvas-edit'))->assertOk();

        $cards = collect($this->actingAs($this->admin)->getJson(route('admin.theme-design.canvas.templates') . '?type=header')->json('templates'))->keyBy('id');

        $this->assertTrue($cards['lib:solar-header']['access']['locked']);
        $this->assertSame('نیازمند خرید', $cards['lib:solar-header']['access']['badge']);
        $this->actingAs($this->admin)->postJson(route('admin.theme-design.canvas.insert-template'), ['template_id' => 'lib:solar-header'])
            ->assertStatus(403)->assertJsonPath('access.badge', 'نیازمند خرید');
    }

    public function test_a_grant_unlocks_only_the_granted_template_and_a_revoke_relocks_it_on_the_next_open(): void
    {
        $this->platformAnswers($this->payload(null, [], ['template:solar-header']));
        $this->actingAs($this->admin)->get(route('admin.theme-design.canvas-edit'))->assertOk();

        $ids = $this->templateIds();
        $this->assertContains('lib:solar-header', $ids);
        $this->assertNotContains('lib:solar-footer', $ids);
        $this->actingAs($this->admin)->postJson(route('admin.theme-design.canvas.insert-template'), ['template_id' => 'lib:solar-header'])
            ->assertOk()->assertJsonPath('zone', 'header');

        $this->platformAnswers($this->payload(null));
        $this->app->forgetScopedInstances();
        $this->actingAs($this->admin)->get(route('admin.theme-design.canvas-edit'))->assertOk();
        $this->assertNotContains('lib:solar-header', $this->templateIds());
    }

    public function test_the_editor_palette_and_template_modal_carry_the_access_state(): void
    {
        $this->platformAnswers($this->payload('solar_energy'));

        $html = $this->actingAs($this->admin)->get(route('admin.theme-design.canvas-edit'))->assertOk()->getContent();

        $this->assertStringContainsString('tpl-asset-badge', $html);
        $this->assertStringContainsString("tpl.access && tpl.access.locked ? showToast(", $html);
        // Every canonical widget is public, so nothing is locked in the palette.
        $this->assertStringNotContainsString('tb-palette-locked-', $html);
    }

    public function test_saving_refuses_a_newly_added_restricted_widget_but_keeps_one_already_on_the_page(): void
    {
        $this->platformAnswers($this->payload(null));
        $locked = ['id' => 'cust_1', 'type' => 'solar_calculator', 'props' => [], 'children' => []];
        $heading = ['id' => 'cust_2', 'type' => 'heading', 'props' => ['text' => 'سلام'], 'children' => []];

        $this->actingAs($this->admin)->putJson(route('admin.theme-design.canvas-save-blocks'), ['page_key' => 'home', 'blocks' => [$heading, $locked]])
            ->assertStatus(403)->assertJsonPath('locked_types', ['solar_calculator']);
        $this->actingAs($this->admin)->putJson(route('admin.theme-design.canvas-save-blocks'), ['page_key' => '__header__', 'blocks' => [$locked]])
            ->assertStatus(403);

        // The site held the widget before its licence lapsed: the stored
        // page keeps it and the rest of the page still saves.
        $draft = json_decode(Setting::get('canvas_draft_data') ?: Setting::get('active_canvas_theme_data'), true);
        $draft['pages']['home']['blocks'] = [$locked];
        Setting::set('canvas_draft_data', json_encode($draft, JSON_UNESCAPED_UNICODE), 'theme');
        $this->actingAs($this->admin)->putJson(route('admin.theme-design.canvas-save-blocks'), ['page_key' => 'home', 'blocks' => [$heading, $locked]])
            ->assertOk()->assertJsonPath('success', true);
    }
}
