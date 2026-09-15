<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\MembershipPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MOD-03 (Full Product Commercial Audit, TASK 2A-CONTINUATION):
 * `module:loyalty_points_badge` reuses the real, already-working
 * `MembershipPointsService::getBalance()` — the exact same call
 * `Account\PointsController::index()` itself uses — no new backend logic,
 * no fabricated balance.
 */
class LoyaltyPointsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
    }

    private function renderer(): \App\Services\ThemeCanvas\ThemeCanvasRenderer
    {
        $renderer = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);
        return $renderer;
    }

    public function test_badge_reflects_the_real_authenticated_users_point_balance(): void
    {
        $user = User::factory()->create();
        MembershipPoint::create([
            'user_id' => $user->id, 'amount' => 350, 'balance_after' => 350,
            'type' => MembershipPoint::TYPE_EARN, 'source' => MembershipPoint::SOURCE_PURCHASE,
            'description' => 'test earn',
        ]);

        $html = $this->actingAs($user)->renderer()->renderBlock([
            'id' => 'lp1', 'type' => 'module:loyalty_points_badge', 'props' => [],
        ]);

        $this->assertStringContainsString('>350<', $html);
        $this->assertStringContainsString(route('account.points.index'), $html);
    }

    public function test_zero_balance_shows_no_badge(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->renderer()->renderBlock([
            'id' => 'lp2', 'type' => 'module:loyalty_points_badge', 'props' => [],
        ]);

        $this->assertStringNotContainsString('background:#f59e0b', $html);
    }

    public function test_guest_sees_nothing_never_a_fabricated_balance(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'lp3', 'type' => 'module:loyalty_points_badge', 'props' => [],
        ]);

        $this->assertSame('', $html);
    }

    public function test_style_full_shows_the_label_text(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->renderer()->renderBlock([
            'id' => 'lp4', 'type' => 'module:loyalty_points_badge',
            'props' => ['style' => 'full', 'label' => 'امتیاز باشگاه مشتریان'],
        ]);

        $this->assertStringContainsString('امتیاز باشگاه مشتریان', $html);
    }

    public function test_expired_points_are_excluded_from_the_rendered_balance(): void
    {
        $user = User::factory()->create();
        MembershipPoint::create([
            'user_id' => $user->id, 'amount' => 500, 'balance_after' => 500,
            'type' => MembershipPoint::TYPE_EARN, 'source' => MembershipPoint::SOURCE_PURCHASE,
            'description' => 'expired', 'expires_at' => now()->subDay(),
        ]);
        MembershipPoint::create([
            'user_id' => $user->id, 'amount' => 20, 'balance_after' => 20,
            'type' => MembershipPoint::TYPE_EARN, 'source' => MembershipPoint::SOURCE_PURCHASE,
            'description' => 'still valid', 'expires_at' => now()->addMonth(),
        ]);

        $html = $this->actingAs($user)->renderer()->renderBlock([
            'id' => 'lp5', 'type' => 'module:loyalty_points_badge', 'props' => [],
        ]);

        $this->assertStringContainsString('>20<', $html);
        $this->assertStringNotContainsString('>520<', $html);
    }
}
