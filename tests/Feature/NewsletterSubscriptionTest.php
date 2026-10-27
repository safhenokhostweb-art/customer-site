<?php

namespace Tests\Feature;

use App\Http\Controllers\Front\NewsletterController;
use App\Models\NewsletterSubscriber;
use App\Services\ThemeBuilder\BlockRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Phase 0.7 (saas-platform ADR-066): the theme newsletter block's backend
 * and the host endpoints the shared renderer wires into the newsletter and
 * search blocks.
 */
class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
    }

    private function subscribe(array $data, bool $json = true): \Illuminate\Testing\TestResponse
    {
        $headers = $json ? ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'] : [];
        return $this->post(route('newsletter.subscribe'), $data, $headers);
    }

    public function test_an_email_is_stored_normalised_and_a_repeat_is_reported_not_duplicated(): void
    {
        $this->subscribe(['contact' => '  Ali@Example.COM ', 'source' => 'blk-1'])
            ->assertCreated()
            ->assertJson(['ok' => true, 'status' => 'subscribed', 'message' => NewsletterController::MSG_SUBSCRIBED]);
        $this->assertDatabaseHas('newsletter_subscribers', ['contact' => 'ali@example.com', 'channel' => 'email', 'source' => 'blk-1']);

        $this->subscribe(['contact' => 'ali@example.com'])
            ->assertOk()
            ->assertJson(['ok' => true, 'status' => 'already', 'message' => NewsletterController::MSG_ALREADY]);
        $this->assertSame(1, NewsletterSubscriber::count());
    }

    public function test_an_iranian_mobile_in_any_digit_script_is_one_subscriber(): void
    {
        $this->subscribe(['contact' => '۰۹۱۲ ۳۴۵ ۶۷۸۹'])->assertCreated();
        $this->subscribe(['contact' => '+989123456789'])->assertOk()->assertJson(['status' => 'already']);
        $this->assertDatabaseHas('newsletter_subscribers', ['contact' => '09123456789', 'channel' => 'mobile']);
        $this->assertSame(1, NewsletterSubscriber::count());
    }

    public function test_invalid_and_empty_contacts_get_a_persian_422(): void
    {
        $this->subscribe(['contact' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonPath('errors.contact.0', NewsletterController::MSG_INVALID);
        $this->subscribe(['contact' => ''])
            ->assertStatus(422)
            ->assertJsonPath('errors.contact.0', NewsletterController::MSG_EMPTY);
        $this->subscribe(['contact' => ['x']])->assertStatus(422);
        $this->subscribe(['contact' => str_repeat('a', 185) . '@x.com'])->assertStatus(422);
        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_the_honeypot_answers_normally_and_stores_nothing(): void
    {
        $this->subscribe(['contact' => 'bot@example.com', 'website' => 'http://spam'])->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_an_unsafe_source_is_not_stored(): void
    {
        $this->subscribe(['contact' => 'a@example.com', 'source' => '<script>'])->assertCreated();
        $this->assertNull(NewsletterSubscriber::first()->source);
    }

    public function test_an_unsubscribed_contact_can_subscribe_again(): void
    {
        NewsletterSubscriber::create(['contact' => 'a@example.com', 'channel' => 'email', 'unsubscribed_at' => now()]);
        $this->subscribe(['contact' => 'A@example.com'])->assertOk()->assertJson(['status' => 'subscribed']);
        $this->assertNull(NewsletterSubscriber::first()->unsubscribed_at);
    }

    public function test_a_plain_form_post_redirects_back_with_the_message(): void
    {
        $this->from('/')->subscribe(['contact' => 'a@example.com'], false)
            ->assertRedirect('/')
            ->assertSessionHas('newsletter_status', NewsletterController::MSG_SUBSCRIBED);
        $this->from('/')->subscribe(['contact' => 'bad'], false)
            ->assertRedirect('/')
            ->assertSessionHasErrors(['contact'], null, 'newsletter');
    }

    public function test_the_endpoint_is_a_csrf_protected_throttled_post(): void
    {
        // Laravel skips CSRF checks under PHPUnit, so assert the route sits in
        // the web group (ValidateCsrfToken) instead of probing for a 419.
        $route = app('router')->getRoutes()->getByName('newsletter.subscribe');
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains('throttle:6,1', $route->gatherMiddleware());
        $this->assertSame(['POST'], $route->methods());

        for ($i = 0; $i < 6; $i++) {
            $this->subscribe(['contact' => "u{$i}@example.com"])->assertSuccessful();
        }
        $this->subscribe(['contact' => 'u7@example.com'])->assertStatus(429);
    }

    public function test_the_renderer_wires_the_real_newsletter_and_search_endpoints(): void
    {
        $this->seedValidLicense(['shop', 'blog']);
        $this->get('/'); // a request on the licensed host, with a session

        $renderer = (new BlockRenderer())->setMode('public');
        $newsletter = $renderer->renderBlock(['type' => 'newsletter_form', 'id' => 'n1', 'props' => []]);
        $this->assertStringContainsString('method="post" action="/newsletter/subscribe" data-tb-newsletter', $newsletter);
        $this->assertStringContainsString('data-tb-block-asset="site_forms"', $newsletter);

        $search = $renderer->renderBlock(['type' => 'search_bar', 'id' => 's1', 'props' => ['scope' => 'posts']]);
        $this->assertStringContainsString('method="get" action="/blog"', $search, 'without advanced_search the blog listing serves posts');
        $this->assertStringContainsString('data-tb-scope="posts"', $search);
        $this->assertStringContainsString('name="q"', $search);
    }

    public function test_the_editor_preview_keeps_the_forms_inert(): void
    {
        $renderer = (new BlockRenderer())->setMode('editor')->setContext(['editor_mode' => true]);
        $html = $renderer->renderBlock(['type' => 'newsletter_form', 'id' => 'n1', 'props' => []]);
        $this->assertStringNotContainsString('data-tb-newsletter', $html);
        $this->assertStringContainsString('onsubmit="event.preventDefault();this.reset();"', $html);
    }

    public function test_advanced_search_routes_scopes_and_suggestions_when_the_module_is_on(): void
    {
        $this->seedValidLicense(['shop', 'blog', 'advanced_search']);
        $this->get('/');

        $html = (new BlockRenderer())->setMode('public')
            ->renderBlock(['type' => 'search_bar', 'id' => 's1', 'props' => ['scope' => 'products']]);
        $this->assertStringContainsString('action="/search"', $html);
        $this->assertStringContainsString('data-tb-suggest="/api/search/suggest"', $html);
        $this->assertStringContainsString('<input type="hidden" name="type" value="products">', $html);
    }
}
