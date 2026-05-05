<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionMiddlewareTest extends TestCase
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

    public function test_staff_without_required_permission_gets_403(): void
    {
        $staffRole = Role::query()->firstOrCreate([
            'slug' => Role::STAFF,
        ], [
            'name' => 'Staff',
            'guard_name' => 'web',
        ]);
        $dashboardPermission = Permission::query()->firstOrCreate([
            'slug' => 'dashboard',
        ], [
            'name' => 'داشبورد',
            'module_slug' => 'dashboard',
            'guard_name' => 'web',
        ]);
        $staffRole->permissions()->sync([$dashboardPermission->id]);

        $user = User::factory()->create(['is_admin' => false]);
        $user->roles()->sync([$staffRole->id]);

        $response = $this->actingAs($user)->get(route('admin.settings.index'));
        $response->assertStatus(403);
    }

    public function test_staff_with_required_permission_can_access_route(): void
    {
        $staffRole = Role::query()->firstOrCreate([
            'slug' => Role::STAFF,
        ], [
            'name' => 'Staff',
            'guard_name' => 'web',
        ]);
        $dashboardPermission = Permission::query()->firstOrCreate([
            'slug' => 'dashboard',
        ], [
            'name' => 'داشبورد',
            'module_slug' => 'dashboard',
            'guard_name' => 'web',
        ]);
        $settingsPermission = Permission::query()->firstOrCreate([
            'slug' => 'settings',
        ], [
            'name' => 'تنظیمات',
            'module_slug' => 'settings',
            'guard_name' => 'web',
        ]);
        $staffRole->permissions()->sync([$dashboardPermission->id, $settingsPermission->id]);

        $user = User::factory()->create(['is_admin' => false]);
        $user->roles()->sync([$staffRole->id]);

        $response = $this->actingAs($user)->get(route('admin.settings.index'));
        $response->assertStatus(200);
    }
}
