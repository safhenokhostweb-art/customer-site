<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Companion to ProfileAvatarStorageRegressionTest, but exercises the real
 * write path end to end (SmartImageUploadService -> MediaStorage::disk())
 * against a genuinely unusable disk root, instead of mocking the service.
 * The write path must still fail loudly and safely: MediaStorage::disk()
 * is reserved for writes and is expected to propagate a broken-root
 * failure (unlike url()/exists()/delete(), which fail soft for reads).
 */
class ProfileAvatarRealStorageFailureTest extends TestCase
{
    use RefreshDatabase;

    private ?string $blockingFile = null;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        if ($this->blockingFile !== null) {
            @unlink($this->blockingFile);
        }
        parent::tearDown();
    }

    public function test_avatar_upload_fails_controlled_and_preserves_existing_avatar_when_uploads_root_is_unusable(): void
    {
        Log::spy();

        $this->blockingFile = sys_get_temp_dir().'/avatar_real_storage_test_blocker_'.uniqid('', true);
        file_put_contents($this->blockingFile, 'not-a-directory');

        Config::set('filesystems.disks.uploads', [
            'driver' => 'local',
            'root' => $this->blockingFile,
            'url' => rtrim(config('app.url'), '/').'/uploads',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ]);

        $user = User::factory()->create(['avatar' => 'avatar/2026/01/existing.webp']);

        $response = $this->actingAs($user)->put(route('account.profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertSessionHasErrors('avatar');

        $user->refresh();
        $this->assertSame('avatar/2026/01/existing.webp', $user->avatar);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('account.profile.avatar_upload_failed', \Mockery::on(
                fn (array $context): bool => $context['user_id'] === $user->id
            ));
    }
}
