<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SmartImageUploadService;
use Illuminate\Http\Request;

class ImageUploadController extends Controller
{
    public function __construct(private SmartImageUploadService $imageService)
    {
    }

    public function store(Request $request)
    {
        $request->validate([
            'file'    => 'required|image|mimes:jpeg,png,gif,webp|max:10240',
            'context' => 'nullable|string|in:' . implode(',', SmartImageUploadService::contextList()),
        ]);

        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            return response()->json(['message' => 'فایل تصویر معتبر ارسال نشده است.'], 422);
        }

        $root = (string) config('filesystems.disks.uploads.root', '');
        if ($root === '') {
            return response()->json(['message' => 'دیسک uploads در filesystems.php تنظیم نشده.'], 500);
        }

        try {
            $result = $this->imageService->upload($file, (string) $request->input('context', 'thumbnail'));
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => config('app.debug') ? $e->getMessage() : 'خطا در پردازش تصویر.',
            ], 500);
        }

        return response()->json($result);
    }
}
