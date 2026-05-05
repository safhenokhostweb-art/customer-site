<?php

namespace Tests\Feature;

use App\Models\MessagingChannel;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleWhatsappTest extends TestCase
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

    public function test_whatsapp_settings(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->get(route('admin.whatsapp.index'));
        $response->assertStatus(200);

        $response = $this->post(route('admin.messaging.store'), [
            'platform' => 'whatsapp',
            'phone' => '989123456789',
            'message' => 'سلام',
            'is_active' => true,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('messaging_channels', [
            'platform' => 'whatsapp',
            'phone' => '989123456789',
            'is_active' => 1,
        ]);

        $channel = MessagingChannel::query()->where('platform', 'whatsapp')->firstOrFail();
        $response = $this->put(route('admin.messaging.update', $channel), [
            'phone' => '989111111111',
            'message' => 'آپدیت شد',
            'is_active' => true,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('messaging_channels', [
            'id' => $channel->id,
            'phone' => '989111111111',
        ]);
    }
}
