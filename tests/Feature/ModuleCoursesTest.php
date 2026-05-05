<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleCoursesTest extends TestCase
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

    public function test_courses_crud(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->post(route('admin.courses.store'), [
            'title' => 'دوره تست',
            'price' => 50000,
            'is_active' => true,
        ]);
        $response->assertRedirect(route('admin.courses.index'));
        $this->assertDatabaseHas('courses', ['title' => 'دوره تست']);

        $course = Course::first();
        $response = $this->get(route('admin.courses.index'));
        $response->assertStatus(200);
        $response->assertSee('دوره تست');
    }
}
