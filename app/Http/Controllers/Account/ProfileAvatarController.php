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
        $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
        $dir = 'avatar/' . date('Y/m');
        $path = $file->storeAs($dir, $filename, 'uploads');

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
