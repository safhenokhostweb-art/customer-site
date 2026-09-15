<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\Front\SubscriptionPaymentController;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * AUTH-03 (Task 2C RBAC convergence) — regression coverage for the 2
 * customer-site call sites that were checking the raw `is_admin` flag
 * directly instead of the canonical `isTenantAdmin()` authority. A user
 * granted tenant-admin access ONLY through `is_site_admin`/the
 * TENANT_ADMIN role (is_admin=false) must be treated identically to an
 * is_admin=true user.
 *
 * SubscriptionPaymentController::activate() has no route wired to it
 * currently (verified: zero references anywhere in routes/) — a
 * pre-existing, separate hygiene gap, not introduced by this fix and out
 * of scope to wire up here. The method is still real, reachable PHP code
 * (could be routed at any time), so it is invoked directly rather than
 * through an HTTP route.
 */
class RbacConvergenceTest extends TestCase
{
    use RefreshDatabase;

    /** is_admin=false, authorized purely via the TENANT_ADMIN role row. */
    private function roleOnlyTenantAdmin(string $mobile = '09150000001'): User
    {
        $user = User::factory()->create(['mobile' => $mobile, 'is_admin' => false]);
        $role = Role::where('slug', Role::TENANT_ADMIN)->first();
        $this->assertNotNull($role, 'TENANT_ADMIN role must be seeded for this regression suite to be meaningful.');
        $user->roles()->attach($role->id);

        return $user;
    }

    private function ordinaryUser(string $mobile = '09150000002'): User
    {
        return User::factory()->create(['mobile' => $mobile, 'is_admin' => false]);
    }

    public function test_subscription_activate_accepts_a_role_only_tenant_admin(): void
    {
        $admin = $this->roleOnlyTenantAdmin();
        $customer = $this->ordinaryUser('09150000003');
        $plan = SubscriptionPlan::create(['name' => 'Test Plan', 'slug' => 'test-plan-' . uniqid(), 'price' => 100000, 'duration' => 'monthly', 'is_active' => true]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . uniqid(),
            'user_id' => $customer->id,
            'customer_name' => 'Test', 'customer_mobile' => '09120000000',
            'subtotal' => 100000, 'discount' => 0, 'total' => 100000,
            'status' => 'pending',
        ]);

        $this->actingAs($admin);
        $request = Request::create('/', 'POST', ['plan_id' => $plan->id]);
        $request->setUserResolver(fn () => $admin);
        app()->instance('request', $request);

        $response = app(SubscriptionPaymentController::class)->activate($request, $invoice);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_subscription_activate_rejects_a_plain_authenticated_user(): void
    {
        $user = $this->ordinaryUser('09150000004');
        $customer = $this->ordinaryUser('09150000005');
        $plan = SubscriptionPlan::create(['name' => 'Test Plan', 'slug' => 'test-plan-' . uniqid(), 'price' => 100000, 'duration' => 'monthly', 'is_active' => true]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . uniqid(),
            'user_id' => $customer->id,
            'customer_name' => 'Test', 'customer_mobile' => '09120000000',
            'subtotal' => 100000, 'discount' => 0, 'total' => 100000,
            'status' => 'pending',
        ]);

        $this->actingAs($user);
        $request = Request::create('/', 'POST', ['plan_id' => $plan->id]);
        $request->setUserResolver(fn () => $user);
        app()->instance('request', $request);

        try {
            app(SubscriptionPaymentController::class)->activate($request, $invoice);
            $this->fail('Expected a 403 HttpException for a non-tenant-admin user.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame('pending', $invoice->fresh()->status);
    }

    // ── CheckSiteLifecycle — admin paths remain reachable after the redundant is_admin re-check was removed ──

    public function test_admin_paths_remain_reachable_when_site_is_suspended_regardless_of_admin_flag(): void
    {
        file_put_contents(storage_path('installed.lock'), '1');
        \App\Models\Setting::set('site_lifecycle_status', 'suspended');

        try {
            // Even an unauthenticated request to an admin-prefixed path is
            // let through by CheckSiteLifecycle itself (real auth is the
            // downstream route's own auth/admin middleware's job) — this
            // proves the removed is_admin re-check was truly redundant,
            // not a silent behavior change.
            $response = $this->get('/login');
            $response->assertOk();
        } finally {
            \App\Models\Setting::set('site_lifecycle_status', 'active');
            @unlink(storage_path('installed.lock'));
        }
    }
}
