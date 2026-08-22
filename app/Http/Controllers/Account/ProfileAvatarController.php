<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileAvatarRequest;
use App\Models\UserActivityLog;
use App\Services\MediaStorage;
use App\Services\SmartImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileAvatarController extends Controller
{
    public function __construct(
        private readonly SmartImageUploadService $imageService,
        private readonly MediaStorage $media,
    ) {
    }

    public function update(ProfileAvatarRequest $request)
    {
        $user = $request->user();

        $file = $request->file('avatar');
        $ext = $file->extension() ?: $file->getClientOriginalExtension();
        if (empty($ext)) {
            return back()->withErrors(['avatar' => 'نوع فایل تصویر شناسایی نشد. لطفاً یک فایل JPEG، PNG، GIF یا WEBP آپلود کنید.']);
        }
        try {
            $result = $this->imageService->upload($file, 'avatar');
            $path = $result['path'];

            if ($user->avatar && ! $this->deleteStoredAvatar($user->avatar)) {
                Log::warning('account.profile.previous_avatar_delete_failed', [
                    'user_id' => $user->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('account.profile.avatar_upload_failed', [
                'user_id' => $user->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors(['avatar' => 'آپلود تصویر پروفایل ناموفق بود. لطفاً دوباره تلاش کنید.']);
        }

        $user->update(['avatar' => $path]);

        UserActivityLog::log('avatar_updated', 'تغییر تصویر پروفایل');

        return back()->with('success', 'تصویر پروفایل با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            $this->deleteStoredAvatar($user->avatar);
            $user->update(['avatar' => null]);
            UserActivityLog::log('avatar_removed', 'حذف تصویر پروفایل');
        }

        return back()->with('success', 'تصویر پروفایل حذف شد.');
    }

    private function deleteStoredAvatar(string $path): bool
    {
        if (str_starts_with($path, 'uploads/admin-avatars/')) {
            return $this->media->delete(substr($path, strlen('uploads/')), 'uploads');
        }

        if (str_starts_with($path, 'uploads/')) {
            return $this->media->delete($path, 'legacy');
        }

        return $this->media->delete($path, 'uploads');
    }
}
