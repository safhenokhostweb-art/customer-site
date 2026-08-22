<?php

namespace Tests\Feature\ProductionStability;

use App\Models\User;
use App\Services\SmartImageUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class AdminProfileImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private bool $installedLockExisted;
    private string|false $installedLockContents;

    protected function setUp(): void
    {
        parent::setUp();
        $this->installedLockExisted = file_exists(storage_path('installed.lock'));
        $this->installedLockContents = $this->installedLockExisted
            ? file_get_contents(storage_path('installed.lock'))
            : false;
        file_put_contents(storage_path('installed.lock'), '1');
        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        if ($this->installedLockExisted) {
            file_put_contents(storage_path('installed.lock'), (string) $this->installedLockContents);
        } else {
            @unlink(storage_path('installed.lock'));
        }
        parent::tearDown();
    }

    public function test_profile_image_upload_uses_canonical_media_service_and_persists_path(): void
    {
        $user = User::factory()->create(['name' => 'Before']);
        $image = UploadedFile::fake()->image('profile.jpg');

        $service = Mockery::mock(SmartImageUploadService::class);
        $service->shouldReceive('upload')
            ->once()
            ->with($image, 'avatar')
            ->andReturn([
                'path' => 'avatar/2026/08/profile.webp',
                'url' => 'https://media.example/avatar/2026/08/profile.webp',
            ]);
        $this->app->instance(SmartImageUploadService::class, $service);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->put(route('admin.profile.update'), [
                'name' => 'After',
                'avatar' => $image,
            ]);

        $response->assertOk()->assertJsonPath('avatar_error', null);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'After',
            'avatar' => 'avatar/2026/08/profile.webp',
        ]);
    }

    public function test_invalid_image_is_rejected_without_changing_existing_image(): void
    {
        $user = User::factory()->create(['avatar' => 'uploads/admin-avatars/existing.jpg']);
        $service = Mockery::mock(SmartImageUploadService::class);
        $service->shouldNotReceive('upload');
        $this->app->instance(SmartImageUploadService::class, $service);

        $response = $this->actingAs($user)->put(route('admin.profile.update'), [
            'name' => 'After',
            'avatar' => UploadedFile::fake()->create('not-image.txt', 2, 'text/plain'),
        ]);

        $response->assertSessionHasErrors('avatar');
        $this->assertSame('uploads/admin-avatars/existing.jpg', $user->fresh()->avatar);
    }

    public function test_storage_failure_is_logged_and_preserves_existing_image_while_text_saves(): void
    {
        Log::spy();
        $user = User::factory()->create([
            'name' => 'Before',
            'avatar' => 'uploads/admin-avatars/existing.jpg',
        ]);
        $image = UploadedFile::fake()->image('profile.jpg');

        $service = Mockery::mock(SmartImageUploadService::class);
        $service->shouldReceive('upload')
            ->once()
            ->andThrow(new \RuntimeException('disk unavailable'));
        $this->app->instance(SmartImageUploadService::class, $service);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->put(route('admin.profile.update'), [
                'name' => 'After',
                'avatar' => $image,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('avatar_error', 'آپلود تصویر پروفایل ناموفق بود. سایر تغییرات ذخیره شد.');

        $user->refresh();
        $this->assertSame('After', $user->name);
        $this->assertSame('uploads/admin-avatars/existing.jpg', $user->avatar);
        Log::shouldHaveReceived('error')->once()->with(
            'admin.profile.avatar_upload_failed',
            Mockery::on(fn (array $context): bool => $context['user_id'] === $user->id
                && $context['exception'] === \RuntimeException::class)
        );
    }
}
