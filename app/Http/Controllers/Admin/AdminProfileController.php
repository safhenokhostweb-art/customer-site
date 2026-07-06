<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminProfileController extends Controller
{
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

        if ($request->hasFile('avatar')) {
            $file    = $request->file('avatar');
            if ($file->isValid()) {
                $destDir = public_path('uploads/admin-avatars');
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0775, true);
                }
                $ext      = $file->guessExtension() ?: 'jpg';
                $filename = 'admin_' . $user->id . '_' . time() . '.' . $ext;
                $file->move($destDir, $filename);
                $update['avatar'] = 'uploads/admin-avatars/' . $filename;
            }
        }

        if (!empty($update)) {
            $user->update($update);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'پروفایل با موفقیت به‌روزرسانی شد.',
                'avatar_url' => $user->fresh()->avatar ? url($user->fresh()->avatar) : null,
            ]);
        }

        return back()->with('success', 'پروفایل با موفقیت به‌روزرسانی شد.');
    }
}
