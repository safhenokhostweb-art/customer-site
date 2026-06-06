<?php

namespace Tests\Feature;

use App\Models\MembershipPoint;
use App\Models\User;
use App\Services\MembershipPointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ProcessBirthdayGiftsCommand tests.
 *
 * Covers:
 *   - User with birthday today receives 200 points
 *   - MembershipPoint row created with SOURCE_BIRTHDAY
 *   - User with birthday today but already received gift this year: skipped
 *   - User with birthday tomorrow: NOT gifted
 *   - User with no birth_date: NOT gifted
 *   - dry-run: no DB changes
 */
class BirthdayGiftsTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function makeUser(?string $birthDate = null): User
    {
        self::$seq++;
        return User::query()->create([
            'name'       => 'کاربر ' . self::$seq,
            'mobile'     => '096' . str_pad(self::$seq, 8, '0', STR_PAD_LEFT),
            'password'   => bcrypt('pass'),
            'is_admin'   => false,
            'birth_date' => $birthDate,
        ]);
    }

    // ── tests ─────────────────────────────────────────────────────────────

    public function test_user_with_birthday_today_receives_points(): void
    {
        $today = now();
        $user  = $this->makeUser(
            $today->copy()->subYears(25)->format('Y-m-d')
        );

        $this->artisan('membership:birthday-gifts')->assertExitCode(0);

        $balance = app(MembershipPointsService::class)->getBalance($user);
        $this->assertEquals(200, $balance);
    }

    public function test_membership_point_row_has_birthday_source(): void
    {
        $today = now();
        $user  = $this->makeUser($today->copy()->subYears(30)->format('Y-m-d'));

        $this->artisan('membership:birthday-gifts')->assertExitCode(0);

        $this->assertDatabaseHas('membership_points', [
            'user_id' => $user->id,
            'source'  => MembershipPoint::SOURCE_BIRTHDAY,
            'amount'  => 200,
        ]);
    }

    public function test_birthday_gift_not_sent_twice_in_same_year(): void
    {
        $today = now();
        $user  = $this->makeUser($today->copy()->subYears(20)->format('Y-m-d'));

        // Gift once
        $this->artisan('membership:birthday-gifts')->assertExitCode(0);
        // Try again
        $this->artisan('membership:birthday-gifts')->assertExitCode(0);

        $count = MembershipPoint::where('user_id', $user->id)
            ->where('source', MembershipPoint::SOURCE_BIRTHDAY)
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_user_with_birthday_tomorrow_not_gifted(): void
    {
        $tomorrow = now()->addDay();
        $user     = $this->makeUser(
            $tomorrow->copy()->subYears(25)->format('Y-m-d')
        );

        $this->artisan('membership:birthday-gifts')->assertExitCode(0);

        $balance = app(MembershipPointsService::class)->getBalance($user);
        $this->assertEquals(0, $balance);
    }

    public function test_user_without_birth_date_not_gifted(): void
    {
        $user = $this->makeUser(null);

        $this->artisan('membership:birthday-gifts')->assertExitCode(0);

        $this->assertEquals(
            0,
            MembershipPoint::where('user_id', $user->id)->count()
        );
    }

    public function test_dry_run_makes_no_db_changes(): void
    {
        $today = now();
        $user  = $this->makeUser($today->copy()->subYears(28)->format('Y-m-d'));

        $this->artisan('membership:birthday-gifts', ['--dry-run' => true])->assertExitCode(0);

        $this->assertEquals(0, MembershipPoint::where('user_id', $user->id)->count());
    }
}
