<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileAvatarRequest;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileAvatarController extends Controller
{
    public function update(ProfileAvatarRequest $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('uploads')->delete($user->avatar);
        }

        $file = $request->file('avatar');
        $ext = $file->extension() ?: $file->getClientOriginalExtension();
        if (empty($ext)) {
            return back()->withErrors(['avatar' => 'نوع فایل تصویر شناسایی نشد. لطفاً یک فایل JPEG، PNG، GIF یا WEBP آپلود کنید.']);
        }
        $filename = Str::random(20) . '.' . $ext;
        $relDir = 'avatar/' . date('Y/m');
        $absDir = public_path('uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relDir));

        try {
            if (!is_dir($absDir)) {
                mkdir($absDir, 0755, true);
            }
            $file->move($absDir, $filename);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Profile avatar upload: ' . $e->getMessage());

            return back()->withErrors(['avatar' => 'آپلود تصویر پروفایل ناموفق بود. لطفاً دوباره تلاش کنید.']);
        }

        $path = $relDir . '/' . $filename;

        $user->update(['avatar' => $path]);

        UserActivityLog::log('avatar_updated', 'تغییر تصویر پروفایل');

        return back()->with('success', 'تصویر پروفایل با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('uploads')->delete($user->avatar);
            $user->update(['avatar' => null]);
            UserActivityLog::log('avatar_removed', 'حذف تصویر پروفایل');
        }

        return back()->with('success', 'تصویر پروفایل حذف شد.');
    }
}
