<?php

namespace Tests\Feature\Account;

use App\Models\Setting;
use App\Support\ResourceSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 2026-11-19 (P1): the customer's resource widget follows the platform's
 * summary — measured/unmeasured state, the platform's own thresholds and
 * the real soft-quota grace dates — instead of hard-coded 80/95 and a
 * permanent "7-day grace period is active" claim.
 */
class ResourceSummaryDisplayTest extends TestCase
{
    use RefreshDatabase;

    private const GB = 1024 ** 3;

    private function summary(array $overrides = []): array
    {
        return array_merge([
            'storage_measured' => true, 'measured_at' => now()->toIso8601String(),
            'warning_percent' => 80, 'critical_percent' => 95,
            'storage_pct' => 50.0, 'storage_bytes' => 5 * self::GB,
            'disk_hard_limit_bytes' => 10 * self::GB, 'storage_limit' => 10 * self::GB,
            'disk_soft_limit_bytes' => 7 * self::GB,
            'soft_exceeded_at' => null, 'soft_grace_days' => 7, 'soft_grace_ends_at' => null, 'soft_grace_expired' => false,
            'memory_limit_mb' => 1024, 'cpu_cores_limit' => 1.0,
        ], $overrides);
    }

    private function widget(array $summary): string
    {
        Setting::set('site_resource_summary', json_encode($summary));

        return view('admin.partials.resource-widget')->render();
    }

    public function test_unmeasured_summary_never_raises_a_level(): void
    {
        $rs = ResourceSummary::fromJson(json_encode($this->summary(['storage_measured' => false, 'storage_pct' => null])));

        $this->assertFalse($rs->isMeasured());
        $this->assertNull($rs->percent());
        $this->assertNull($rs->level());
        $this->assertFalse($rs->softExceeded());
    }

    public function test_platform_thresholds_are_used_instead_of_hard_coded_ones(): void
    {
        $rs = ResourceSummary::fromJson(json_encode($this->summary(['storage_pct' => 85.0, 'warning_percent' => 90, 'critical_percent' => 98])));

        $this->assertNull($rs->level(), '85% is below a 90% warning threshold set on the platform.');
    }

    public function test_soft_state_follows_the_platform_grace_timestamps(): void
    {
        $active = ResourceSummary::fromJson(json_encode($this->summary([
            'storage_pct' => 75.0, 'storage_bytes' => (int) (7.5 * self::GB),
            'soft_exceeded_at' => now()->subDays(2)->toIso8601String(),
            'soft_grace_ends_at' => now()->addDays(5)->toIso8601String(),
        ])));
        $this->assertSame('soft', $active->level());
        $this->assertFalse($active->graceExpired());

        $expired = ResourceSummary::fromJson(json_encode($this->summary([
            'storage_pct' => 75.0, 'soft_exceeded_at' => now()->subDays(9)->toIso8601String(),
            'soft_grace_ends_at' => now()->subDays(2)->toIso8601String(), 'soft_grace_expired' => true,
        ])));
        $this->assertTrue($expired->graceExpired());
    }

    public function test_widget_is_hidden_for_an_unmeasured_summary(): void
    {
        $html = $this->widget($this->summary(['storage_measured' => false, 'storage_pct' => null]));

        $this->assertStringNotContainsString('هشدار', $html);
        $this->assertStringNotContainsString('بحران', $html);
    }

    public function test_widget_shows_the_real_grace_end_date_and_expiry(): void
    {
        $ends = now()->addDays(5);
        $html = $this->widget($this->summary([
            'storage_pct' => 75.0, 'storage_bytes' => (int) (7.5 * self::GB),
            'soft_exceeded_at' => now()->subDays(2)->toIso8601String(), 'soft_grace_ends_at' => $ends->toIso8601String(),
        ]));
        $this->assertStringContainsString(date_fa($ends), $html);

        $expiredHtml = $this->widget($this->summary([
            'storage_pct' => 75.0, 'storage_bytes' => (int) (7.5 * self::GB),
            'soft_exceeded_at' => now()->subDays(9)->toIso8601String(),
            'soft_grace_ends_at' => now()->subDays(2)->toIso8601String(), 'soft_grace_expired' => true,
        ]));
        $this->assertStringContainsString('به پایان رسیده', $expiredHtml);
    }

    public function test_widget_reports_the_actual_percentage_not_a_fixed_95(): void
    {
        $html = $this->widget($this->summary(['storage_pct' => 96.4, 'storage_bytes' => (int) (9.64 * self::GB)]));

        $this->assertStringContainsString(num_fa(96.4, 1), $html);
    }
}
