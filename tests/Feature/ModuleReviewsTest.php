<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleReviewsTest extends TestCase
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

    public function test_reviews_index(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $product = Product::create(['title' => 'تست', 'slug' => 'test', 'price' => 1000, 'is_active' => true]);
        Review::create([
            'reviewable_type' => Product::class,
            'reviewable_id' => $product->id,
            'rating' => 5,
            'body' => 'عالی',
            'is_approved' => false,
        ]);

        $this->actingAs($user);
        $response = $this->get(route('admin.reviews.index'));
        $response->assertStatus(200);
        $response->assertSee('عالی');
    }
}
