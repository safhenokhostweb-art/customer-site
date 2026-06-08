<?php

namespace Tests\Feature;

use App\Helpers\Module;
use App\Jobs\DispatchWebhookJob;
use App\Models\License;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Models\WebhookEvent;
use App\Services\WebhookDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use ReflectionClass;
use Tests\TestCase;

/**
 * Webhook system tests: dispatch service, job, and admin CRUD.
 */
class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');

        $ref  = new ReflectionClass(Module::class);
        $prop = $ref->getProperty('resolved');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        License::query()->delete();
        License::create([
            'license_key'       => 'WEBHOOK-TEST-LICENSE',
            'domain'            => '',
            'expires_at'        => now()->addYear(),
            'modules'           => ['subscription'],
            'platform_valid'    => true,
            'last_validated_at' => now(),
        ]);
    }

    private static int $seq = 0;

    private function makeAdmin(): User
    {
        self::$seq++;
        return User::factory()->create([
            'mobile'   => '091' . str_pad((string) self::$seq, 8, '0', STR_PAD_LEFT),
            'is_admin' => true,
        ]);
    }

    private function makeEndpoint(array $events = ['subscription.created']): WebhookEndpoint
    {
        return WebhookEndpoint::create([
            'url'       => 'https://example.com/webhook',
            'events'    => $events,
            'secret'    => 'test-secret-key',
            'is_active' => true,
        ]);
    }

    // ─── WebhookDispatchService ────────────────────────────────────────────────

    public function test_dispatch_creates_webhook_event_and_queues_job(): void
    {
        Queue::fake();
        $endpoint = $this->makeEndpoint(['subscription.created']);

        $service = app(WebhookDispatchService::class);
        $service->dispatch('subscription.created', ['subscription_id' => 1, 'plan' => 'حرفه‌ای']);

        $this->assertDatabaseHas('webhook_events', [
            'webhook_endpoint_id' => $endpoint->id,
            'event_type'          => 'subscription.created',
            'status'              => 'pending',
        ]);
        Queue::assertPushed(DispatchWebhookJob::class);
    }

    public function test_dispatch_does_not_queue_for_inactive_endpoint(): void
    {
        Queue::fake();
        $this->makeEndpoint(['subscription.created']);
        WebhookEndpoint::query()->update(['is_active' => false]);

        app(WebhookDispatchService::class)->dispatch('subscription.created', ['x' => 1]);

        Queue::assertNotPushed(DispatchWebhookJob::class);
        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_dispatch_skips_endpoint_not_subscribed_to_event(): void
    {
        Queue::fake();
        $this->makeEndpoint(['subscription.cancelled']); // only cancelled, not created

        app(WebhookDispatchService::class)->dispatch('subscription.created', ['x' => 1]);

        Queue::assertNotPushed(DispatchWebhookJob::class);
        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_dispatch_multiple_endpoints(): void
    {
        Queue::fake();
        $this->makeEndpoint(['subscription.created']);
        WebhookEndpoint::create([
            'url' => 'https://other.com/hook', 'events' => ['subscription.created', 'subscription.cancelled'],
            'secret' => 'secret2', 'is_active' => true,
        ]);

        app(WebhookDispatchService::class)->dispatch('subscription.created', ['x' => 1]);

        Queue::assertPushed(DispatchWebhookJob::class, 2);
        $this->assertDatabaseCount('webhook_events', 2);
    }

    // ─── DispatchWebhookJob ────────────────────────────────────────────────────

    public function test_job_sends_http_post_with_signature(): void
    {
        Http::fake(['https://example.com/*' => Http::response('ok', 200)]);
        $endpoint = $this->makeEndpoint(['subscription.renewed']);
        $event = WebhookEvent::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type'          => 'subscription.renewed',
            'payload'             => ['subscription_id' => 5],
            'status'              => 'pending',
            'attempts'            => 0,
        ]);

        (new DispatchWebhookJob($event->id))->handle();

        Http::assertSentCount(1);
        $event->refresh();
        $this->assertEquals('sent', $event->status);
        $this->assertEquals(200, $event->response_code);
        $this->assertEquals(1, $event->attempts);
    }

    public function test_job_marks_failed_on_non_2xx(): void
    {
        Http::fake(['https://example.com/*' => Http::response('error', 500)]);
        $endpoint = $this->makeEndpoint(['subscription.cancelled']);
        $event = WebhookEvent::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type'          => 'subscription.cancelled',
            'payload'             => ['subscription_id' => 3],
            'status'              => 'pending',
            'attempts'            => 0,
        ]);

        try {
            (new DispatchWebhookJob($event->id))->handle();
        } catch (\Throwable) {
            // expected — job throws to trigger queue retry
        }

        $event->refresh();
        $this->assertEquals('failed', $event->status);
        $this->assertEquals(500, $event->response_code);
    }

    public function test_job_uses_hmac_signature_header(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
        $endpoint = $this->makeEndpoint(['subscription.created']);
        $event = WebhookEvent::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type'          => 'subscription.created',
            'payload'             => ['subscription_id' => 9],
            'status'              => 'pending',
            'attempts'            => 0,
        ]);

        (new DispatchWebhookJob($event->id))->handle();

        Http::assertSent(function ($request) {
            return str_starts_with($request->header('X-Webhook-Signature')[0] ?? '', 'sha256=');
        });
    }

    public function test_job_does_nothing_for_missing_event(): void
    {
        Http::fake();
        (new DispatchWebhookJob(99999))->handle(); // non-existent ID
        Http::assertNothingSent();
    }

    // ─── Admin CRUD ───────────────────────────────────────────────────────────

    public function test_admin_can_list_webhooks(): void
    {
        $admin = $this->makeAdmin();
        $this->makeEndpoint(['subscription.created']);

        $response = $this->actingAs($admin)->get(route('admin.webhooks.index'));
        $response->assertOk();
        $response->assertSee('https://example.com/webhook');
    }

    public function test_admin_can_create_webhook_endpoint(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.webhooks.store'), [
            'url'       => 'https://crm.example.com/webhook',
            'events'    => ['subscription.created', 'subscription.cancelled'],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.webhooks.index'));
        $this->assertDatabaseHas('webhook_endpoints', [
            'url' => 'https://crm.example.com/webhook',
        ]);
        $endpoint = WebhookEndpoint::where('url', 'https://crm.example.com/webhook')->first();
        $this->assertNotEmpty($endpoint->secret);
        $this->assertContains('subscription.created', $endpoint->events);
    }

    public function test_admin_webhook_create_validates_url(): void
    {
        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->post(route('admin.webhooks.store'), [
            'url'    => 'not-a-valid-url',
            'events' => ['subscription.created'],
        ]);
        $response->assertSessionHasErrors('url');
    }

    public function test_admin_webhook_create_requires_at_least_one_event(): void
    {
        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->post(route('admin.webhooks.store'), [
            'url'    => 'https://crm.example.com/webhook',
            'events' => [],
        ]);
        $response->assertSessionHasErrors('events');
    }

    public function test_admin_can_update_webhook(): void
    {
        $admin    = $this->makeAdmin();
        $endpoint = $this->makeEndpoint(['subscription.created']);

        $response = $this->actingAs($admin)->put(route('admin.webhooks.update', $endpoint), [
            'url'    => 'https://updated.example.com/wh',
            'events' => ['subscription.upgraded'],
        ]);

        $response->assertRedirect(route('admin.webhooks.show', $endpoint));
        $this->assertDatabaseHas('webhook_endpoints', ['url' => 'https://updated.example.com/wh']);
    }

    public function test_admin_can_delete_webhook(): void
    {
        $admin    = $this->makeAdmin();
        $endpoint = $this->makeEndpoint();

        $response = $this->actingAs($admin)->delete(route('admin.webhooks.destroy', $endpoint));
        $response->assertRedirect(route('admin.webhooks.index'));
        $this->assertDatabaseMissing('webhook_endpoints', ['id' => $endpoint->id]);
    }

    public function test_admin_can_rotate_secret(): void
    {
        $admin    = $this->makeAdmin();
        $endpoint = $this->makeEndpoint();
        $oldSecret = $endpoint->secret;

        $this->actingAs($admin)->post(route('admin.webhooks.rotate-secret', $endpoint));
        $this->assertNotEquals($oldSecret, $endpoint->fresh()->secret);
    }

    // ─── SubscriptionService integration ─────────────────────────────────────

    public function test_subscription_service_dispatches_webhook_on_subscribe(): void
    {
        Queue::fake();
        $this->makeEndpoint(['subscription.created']);

        self::$seq++;
        $user = User::factory()->create([
            'mobile' => '093' . str_pad((string) self::$seq, 8, '0', STR_PAD_LEFT),
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'پایه', 'slug' => 'basic-' . uniqid(),
            'price' => 0, 'duration' => 'month', 'duration_count' => 1,
            'is_active' => true,
        ]);

        app(\App\Services\SubscriptionService::class)->subscribe($user, $plan);

        Queue::assertPushed(DispatchWebhookJob::class);
        $this->assertDatabaseHas('webhook_events', ['event_type' => 'subscription.created']);
    }
}
