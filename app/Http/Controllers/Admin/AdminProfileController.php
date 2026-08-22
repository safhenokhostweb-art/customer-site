<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SmartImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminProfileController extends Controller
{
    public function __construct(
        private readonly SmartImageUploadService $imageService,
    ) {
    }

    public function index()
    {
        return view('admin.profile.index', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'     => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'avatar'   => ['nullable', 'image', 'max:2048'],
        ]);

        $update = [];

        if (!empty($data['name'])) {
            $update['name'] = $data['name'];
        }

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
            $update['force_password_change'] = false;
        }

        $avatarError = null;
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            if ($file->isValid()) {
                try {
                    $result = $this->imageService->upload($file, 'avatar');
                    $update['avatar'] = $result['path'];
                } catch (\Throwable $e) {
                    Log::error('admin.profile.avatar_upload_failed', [
                        'user_id' => $user->id,
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                    ]);
                    $avatarError = 'آپلود تصویر پروفایل ناموفق بود. سایر تغییرات ذخیره شد.';
                }
            } else {
                $avatarError = 'فایل تصویر ارسال‌شده معتبر نیست.';
            }
        }

        if (!empty($update)) {
            $user->update($update);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'message'      => 'پروفایل با موفقیت به‌روزرسانی شد.',
                'avatar_url'   => $user->fresh()->avatar_url,
                'avatar_error' => $avatarError,
            ]);
        }

        if ($avatarError) {
            return back()->withErrors(['avatar' => $avatarError])
                ->with('success', 'پروفایل با موفقیت به‌روزرسانی شد.');
        }

        return back()->with('success', 'پروفایل با موفقیت به‌روزرسانی شد.');
    }
}
