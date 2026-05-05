<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleSubscriptionTest extends TestCase
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

    public function test_subscription_crud(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->post(route('admin.subscriptions.store'), [
            'name' => 'پلن ماهانه',
            'price' => 100000,
            'duration' => 'month',
            'duration_count' => 1,
            'is_active' => true,
        ]);
        $response->assertRedirect(route('admin.subscriptions.index'));
        $this->assertDatabaseHas('subscription_plans', ['name' => 'پلن ماهانه']);

        $plan = SubscriptionPlan::first();
        $response = $this->get(route('admin.subscriptions.index'));
        $response->assertStatus(200);
        $response->assertSee('پلن ماهانه');
    }
}
