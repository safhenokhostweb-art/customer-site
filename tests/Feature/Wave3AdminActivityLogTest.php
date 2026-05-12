<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\License;
use App\Models\User;
use App\Services\AdminActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Wave 5.4 — Regression tests for Wave 3 / P1-2 (AdminActivityLog model + helper).
 *
 * Coverage:
 *   - admin_activity_log table exists with the expected columns.
 *   - AdminActivityLogger::log() writes a row with action, description,
 *     admin_user_id (from Auth::id()), subject_type/id, and properties.
 *   - Auto-captures Auth::id() when an admin is logged in.
 *   - Defaults description from AdminActivityLog::actionLabel() when omitted.
 *   - Returns null and writes nothing when the table is missing
 *     (schema-guarded; design contract).
 *   - actionLabel() returns Persian labels for known actions and falls
 *     back to the raw action key for unknown actions.
 *   - properties is array-cast on the model.
 */
class Wave3AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_activity_log_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('admin_activity_log'));
        $this->assertTrue(Schema::hasColumns('admin_activity_log', [
            'id', 'admin_user_id', 'action', 'description',
            'subject_type', 'subject_id', 'properties',
            'ip_address', 'user_agent', 'created_at', 'updated_at',
        ]));
    }

    public function test_log_writes_row_with_explicit_description_and_properties(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $row = AdminActivityLogger::log(
            'license.refreshed',
            'بروزرسانی دستی لایسنس',
            null,
            ['domain' => 'p12.test', 'channel' => 'manual']
        );

        $this->assertNotNull($row);
        $this->assertSame('license.refreshed', $row->action);
        $this->assertSame('بروزرسانی دستی لایسنس', $row->description);
        $this->assertSame($admin->id, $row->admin_user_id);
        $this->assertIsArray($row->properties);
        $this->assertSame('p12.test', $row->properties['domain']);
        $this->assertSame('manual', $row->properties['channel']);
    }

    public function test_log_defaults_description_from_action_label_when_omitted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $row = AdminActivityLogger::log('license.refreshed_config');

        $this->assertNotNull($row);
        $this->assertSame('بروزرسانی تنظیمات از پلتفرم', $row->description);
    }

    public function test_log_captures_subject_type_and_id(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $license = License::create([
            'license_key' => 'KEY1-KEY2-KEY3-KEY4',
            'domain' => 'p12-subj.test',
        ]);

        $row = AdminActivityLogger::log(
            'license.activated',
            'فعال‌سازی',
            $license,
            ['domain' => 'p12-subj.test']
        );

        $this->assertSame(License::class, $row->subject_type);
        $this->assertSame($license->id, (int) $row->subject_id);
    }

    public function test_log_returns_null_when_table_missing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        Schema::drop('admin_activity_log');

        $result = AdminActivityLogger::log('module.settings_updated', 'تست بدون جدول');
        $this->assertNull($result, 'logger must fail safely (no exception, no row) when the table is missing');
    }

    public function test_action_label_returns_persian_for_known_actions(): void
    {
        $this->assertSame('ویرایش تنظیمات ماژول', AdminActivityLog::actionLabel('module.settings_updated'));
        $this->assertSame('بازنشانی تنظیمات ماژول', AdminActivityLog::actionLabel('module.settings_reset'));
        $this->assertSame('فعال‌سازی لایسنس', AdminActivityLog::actionLabel('license.activated'));
        $this->assertSame('بروزرسانی لایسنس', AdminActivityLog::actionLabel('license.refreshed'));
        $this->assertSame('بروزرسانی تنظیمات از پلتفرم', AdminActivityLog::actionLabel('license.refreshed_config'));
        $this->assertSame('بروزرسانی فهرست ماژول‌ها', AdminActivityLog::actionLabel('license.refreshed_modules'));
        $this->assertSame('ویرایش تنظیمات', AdminActivityLog::actionLabel('setting.updated'));
    }

    public function test_action_label_falls_back_to_action_key_for_unknown_actions(): void
    {
        $this->assertSame('totally.unknown', AdminActivityLog::actionLabel('totally.unknown'));
    }

    public function test_properties_are_array_cast(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        AdminActivityLogger::log('setting.updated', 'تست', null, ['k' => 'v', 'n' => 42]);

        $row = AdminActivityLog::latest('id')->first();
        $this->assertIsArray($row->properties);
        $this->assertSame('v', $row->properties['k']);
        $this->assertSame(42, $row->properties['n']);
    }

    public function test_admin_user_relation_resolves(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        AdminActivityLogger::log('module.settings_updated', 'rel-test');
        $row = AdminActivityLog::latest('id')->first();

        $this->assertNotNull($row->adminUser);
        $this->assertSame($admin->id, $row->adminUser->id);
    }
}
