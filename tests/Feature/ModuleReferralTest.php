<?php

namespace Tests\Feature;

use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleReferralTest extends TestCase
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

    public function test_referral_index(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        ReferralCode::create(['user_id' => $user->id, 'code' => 'REF123', 'is_active' => true]);

        $this->actingAs($user);
        $response = $this->get(route('admin.referrals.index'));
        $response->assertStatus(200);
        $response->assertSee('REF123');
    }
}
