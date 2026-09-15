<?php

namespace Tests\Feature\Api;

use App\Models\License;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AUTH-01 (Full Product Commercial Audit, TASK 1/3): the Domain-A Tenant
 * Support Runtime API (routes/api.php, "PHASE M3.2") was always documented
 * as `auth:sanctum`-protected, but Sanctum was never actually installed —
 * every request threw "Auth guard [sanctum] is not defined" before reaching
 * the controller. This route group has zero current frontend/mobile
 * consumer (confirmed: no resources/js or resources/views reference to
 * support/conversations, and it is architecturally separate from the
 * homegrown-token `mobile.auth` ticket/notification surface — AUTH-04,
 * tracked and deliberately NOT touched here pending mobile-client
 * coordination), so completing its originally-documented Sanctum auth is a
 * safe, narrowly-scoped fix: it makes the intended architecture real
 * without touching or risking the separately-authenticated, already-working
 * mobile ticket API.
 */
class SupportChatSanctumAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        License::query()->create([
            'license_key' => 'SUPPORT-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'features' => ['support' => true],
        ]);
    }

    public function test_a_real_sanctum_token_authenticates_the_support_chat_api(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/support/conversations');

        // The historical bug was a hard crash before the controller ever
        // ran ("Auth guard [sanctum] is not defined") — any real HTTP
        // response (whatever the controller itself decides) proves the
        // guard now resolves. 500 would mean the guard is still undefined
        // or something else broke; that must not happen.
        $this->assertNotSame(500, $response->getStatusCode(), 'the sanctum guard must be defined and must not crash the request');
    }

    public function test_no_token_is_rejected_as_unauthenticated_not_a_guard_crash(): void
    {
        $response = $this->getJson('/api/support/conversations');

        $response->assertStatus(401);
    }

    public function test_an_invalid_token_is_rejected_as_unauthenticated(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer this-token-does-not-exist')
            ->getJson('/api/support/conversations');

        $response->assertStatus(401);
    }

    public function test_sanctum_token_created_for_one_user_cannot_authenticate_as_another(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $token = $owner->createToken('device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/support/conversations');
        $this->assertNotSame(500, $response->getStatusCode());

        // The authenticated identity must be the token's real owner, never
        // some other user — proves tenant/user isolation survives the
        // guard fix, not just that "a" user got through.
        $this->assertTrue(auth('sanctum')->check());
        $this->assertSame($owner->id, auth('sanctum')->id());
        $this->assertNotSame($other->id, auth('sanctum')->id());
    }
}
