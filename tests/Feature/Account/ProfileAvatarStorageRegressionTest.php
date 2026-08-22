<?php

namespace Tests\Feature\Account;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression coverage for the MediaStorage refactor (see
 * docs/storage-regression/STORAGE_REGRESSION_REPORT.md):
 *
 * - Existing avatars / blog images keep resolving after the refactor.
 * - New avatar uploads succeed and update the DB path.
 * - Upload failures are surfaced to the user instead of a silent no-op.
 */
class ProfileAvatarStorageRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        Storage::fake('uploads');
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    public function test_customer_avatar_upload_succeeds_and_updates_db_path(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('account.profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionDoesntHaveErrors();

        $user->refresh();
        $this->assertStringStartsWith('avatar/', $user->avatar);

        Storage::disk('uploads')->assertExists($user->avatar);
        $this->assertStringEndsWith($user->avatar, $user->avatar_url);

    }

    public function test_customer_avatar_upload_rejects_invalid_file_with_a_visible_error(): void
    {
        $user = User::factory()->create();

        // A non-image upload must fail visibly (ProfileAvatarRequest validation
        // + the account layout's global $errors->any() block), not silently
        // no-op the way AdminProfileController historically did for
        // $file->isValid() === false.
        $response = $this->actingAs($user)->put(route('account.profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->create('not-an-image.txt', 10, 'text/plain'),
        ]);

        $response->assertSessionHasErrors('avatar');
        $user->refresh();
        $this->assertNull($user->avatar);
    }

    public function test_existing_legacy_blog_image_still_resolves_after_the_refactor(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/featured/2026/02/old.png', 'binary-data');

        $user = User::factory()->create();
        $post = Post::create([
            'title'          => 'پست قدیمی',
            'slug'           => 'legacy-image-post',
            'body'           => 'متن',
            'user_id'        => $user->id,
            'featured_image' => 'uploads/featured/2026/02/old.png',
            'is_published'   => true,
            'editorial_status' => Post::EDITORIAL_PUBLISHED,
        ]);

        $url = \App\Helpers\ImageHelper::url($post->featured_image);

        $this->assertStringEndsWith('uploads/featured/2026/02/old.png', $url);
    }

    public function test_new_blog_image_upload_path_resolves_through_the_uploads_disk(): void
    {
        Storage::disk('uploads')->put('featured/2026/08/new.webp', 'binary-data');

        $url = \App\Helpers\ImageHelper::url('featured/2026/08/new.webp');

        $this->assertStringEndsWith('featured/2026/08/new.webp', $url);
        Storage::disk('uploads')->assertExists('featured/2026/08/new.webp');
    }
}
