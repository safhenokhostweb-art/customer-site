<?php

namespace Tests\Feature;

use App\Models\SocialLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleSocialLinksTest extends TestCase
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

    public function test_social_links_crud(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->post(route('admin.social-links.store'), [
            'platform' => 'instagram',
            'url' => 'https://instagram.com/test',
            'is_active' => true,
        ]);
        $response->assertRedirect(route('admin.social-links.index'));
        $this->assertDatabaseHas('social_links', ['platform' => 'instagram']);

        $link = SocialLink::first();
        $response = $this->get(route('admin.social-links.index'));
        $response->assertStatus(200);
        $response->assertSee('instagram');
    }
}
