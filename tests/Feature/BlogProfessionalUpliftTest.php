<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogProfessionalUpliftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents(storage_path('installed.lock'), '1');
        License::query()->create([
            'license_key' => 'LIC-BLOG-001',
            'domain' => 'localhost',
            'expires_at' => now()->addDays(30),
            'modules' => ['blog'],
            'platform_valid' => true,
            'last_validated_at' => now(),
        ]);

        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    public function test_admin_can_schedule_blog_post_with_seo_fallbacks(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $publishAt = now()->addHours(4)->format('Y-m-d H:i:s');

        $response = $this->actingAs($admin)->post(route('admin.posts.store'), [
            'title' => 'راهنمای کامل سئو',
            'slug' => 'seo-guide',
            'body' => str_repeat('متن تست سئو ', 40),
            'save_action' => 'schedule',
            'published_at' => $publishAt,
        ]);

        $response->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseHas('posts', [
            'title' => 'راهنمای کامل سئو',
            'slug' => 'seo-guide',
            'is_published' => 1,
            'editorial_status' => Post::EDITORIAL_PUBLISHED,
        ]);

        $post = Post::query()->where('slug', 'seo-guide')->firstOrFail();
        $this->assertNotNull($post->published_at);
        $this->assertSame('راهنمای کامل سئو', $post->meta_title);
        $this->assertNotEmpty($post->meta_description);
    }

    public function test_publish_action_requires_non_empty_content(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.posts.store'), [
            'title' => 'بدون محتوا',
            'slug' => 'empty-content',
            'save_action' => 'publish',
        ]);

        $response->assertSessionHasErrors(['body']);
        $this->assertDatabaseMissing('posts', ['slug' => 'empty-content']);
    }

    public function test_future_scheduled_posts_are_hidden_from_public_blog_listing(): void
    {
        $user = User::factory()->create();

        Post::query()->create([
            'title' => 'منتشرشده',
            'slug' => 'published-post',
            'body' => 'قابل مشاهده',
            'user_id' => $user->id,
            'is_published' => true,
            'editorial_status' => Post::EDITORIAL_PUBLISHED,
            'published_at' => now()->subHour(),
        ]);

        Post::query()->create([
            'title' => 'زمان‌بندی‌شده آینده',
            'slug' => 'future-scheduled-post',
            'body' => 'نباید نمایش داده شود',
            'user_id' => $user->id,
            'is_published' => true,
            'editorial_status' => Post::EDITORIAL_PUBLISHED,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->get(route('blog.index'));
        $response->assertOk();
        $response->assertSee('منتشرشده');
        $response->assertDontSee('زمان‌بندی‌شده آینده');
    }

    public function test_public_blog_hides_posts_not_editorially_published(): void
    {
        $user = User::factory()->create();

        Post::query()->create([
            'title' => 'فقط پیش‌نویس',
            'slug' => 'draft-only',
            'body' => 'مخفی',
            'user_id' => $user->id,
            'is_published' => false,
            'editorial_status' => Post::EDITORIAL_DRAFT,
        ]);

        $response = $this->get(route('blog.index'));
        $response->assertOk();
        $response->assertDontSee('فقط پیش‌نویس');
    }
}
