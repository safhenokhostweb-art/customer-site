<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleSmsTest extends TestCase
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

    public function test_sms_settings(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->get(route('admin.sms.index'));
        $response->assertStatus(200);

        $response = $this->post(route('admin.sms.update'), [
            'sms_provider' => 'kavenegar',
            'sms_api_key' => 'test-key',
            'sms_line_number' => '1000',
        ]);
        $response->assertRedirect();
        $this->assertEquals('kavenegar', Setting::get('sms_provider'));
    }
}
