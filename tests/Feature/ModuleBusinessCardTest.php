<?php

namespace Tests\Feature;

use App\Models\BusinessCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleBusinessCardTest extends TestCase
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

    public function test_business_card_show(): void
    {
        $card = BusinessCard::create([
            'name' => 'علی احمدی',
            'title' => 'مدیر فروش',
            'slug' => 'ali-ahmadi',
            'phone' => '09121234567',
            'is_active' => true,
        ]);

        $response = $this->get(route('business-card.show', $card->slug));
        $response->assertStatus(200);
        $response->assertSee('علی احمدی');
        $response->assertSee('مدیر فروش');
    }
}
