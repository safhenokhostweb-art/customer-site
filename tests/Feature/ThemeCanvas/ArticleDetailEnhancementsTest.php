<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Commercial Productization fase-2 — real gaps found while auditing the
 * official article-detail route (BlogController::show / blog/show.blade.php)
 * against the requested checklist: author byline, table of contents, share
 * links, and a CTA were genuinely absent. This uses the existing real route,
 * not a new/fake page.
 */
class ArticleDetailEnhancementsTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
        config(['site.owner_site' => true]);
        $this->seedValidLicense(['blog']);
    }

    private function makePost(array $overrides = []): Post
    {
        $user = User::factory()->create(['name' => 'سارا محمدی']);

        return Post::create(array_merge([
            'title' => 'راهنمای کامل فروش آنلاین',
            'slug' => 'complete-online-sales-guide',
            'body' => '<h2>مقدمه</h2><p>متن مقدمه</p><h2>گام اول</h2><p>متن گام اول</p><h3>جزئیات</h3><p>جزئیات بیشتر</p>',
            'user_id' => $user->id,
            'is_published' => true,
            'editorial_status' => Post::EDITORIAL_PUBLISHED,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    public function test_article_shows_a_real_author_byline(): void
    {
        $post = $this->makePost();

        $response = $this->get(route('blog.show', $post->slug));
        $response->assertOk();
        $response->assertSee('سارا محمدی');
    }

    public function test_article_with_multiple_headings_shows_a_real_table_of_contents_linked_to_anchored_headings(): void
    {
        $post = $this->makePost();

        $response = $this->get(route('blog.show', $post->slug));
        $response->assertOk();
        $response->assertSee('فهرست مطالب');
        $response->assertSee('مقدمه');
        $response->assertSee('گام اول');
        $response->assertSee('href="#مقدمه"', false);
    }

    public function test_article_with_a_single_heading_omits_the_empty_toc_shell(): void
    {
        $post = $this->makePost(['body' => '<h2>تنها عنوان</h2><p>متن</p>', 'slug' => 'single-heading-post']);

        $response = $this->get(route('blog.show', $post->slug));
        $response->assertOk();
        $response->assertDontSee('فهرست مطالب');
    }

    public function test_article_shows_real_share_links_and_a_closing_cta(): void
    {
        $post = $this->makePost();

        $response = $this->get(route('blog.show', $post->slug));
        $response->assertOk();
        $response->assertSee('اشتراک‌گذاری');
        $response->assertSee('تلگرام');
        $response->assertSee('کپی لینک');
        $response->assertSee('آماده‌اید کسب‌وکار خود را آنلاین کنید؟');
    }
}
