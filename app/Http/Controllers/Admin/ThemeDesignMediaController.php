<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ThemeDesignMediaController extends Controller
{
    private const DISK      = 'public';
    private const DIR       = 'theme-media';
    private const MAX_BYTES = 5 * 1024 * 1024;

    public function index(): JsonResponse
    {
        $files  = Storage::disk(self::DISK)->files(self::DIR);
        $images = array_map(fn ($f) => [
            'url'  => Storage::disk(self::DISK)->url($f),
            'name' => basename($f),
            'size' => Storage::disk(self::DISK)->size($f),
        ], $files);

        usort($images, fn ($a, $b) => strcmp($b['name'], $a['name']));
        return response()->json(['images' => array_values($images)]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:5120|mimes:jpg,jpeg,png,gif,webp,svg',
        ]);

        $file = $request->file('file');
        if ($file->getSize() > self::MAX_BYTES) {
            return response()->json(['error' => 'حداکثر اندازه ۵ مگابایت'], 422);
        }

        $path = $file->store(self::DIR, self::DISK);
        if (!$path) {
            return response()->json(['error' => 'خطا در ذخیره فایل'], 500);
        }

        return response()->json([
            'url'  => Storage::disk(self::DISK)->url($path),
            'name' => basename($path),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $url  = $request->input('url', '');
        $base = Storage::disk(self::DISK)->url('');
        if (!str_starts_with($url, $base)) {
            return response()->json(['error' => 'مسیر نامعتبر'], 422);
        }

        $path = ltrim(str_replace($base, '', $url), '/');
        if (!str_starts_with($path, self::DIR . '/')) {
            return response()->json(['error' => 'دسترسی رد شد'], 403);
        }

        Storage::disk(self::DISK)->delete($path);
        return response()->json(['ok' => true]);
    }
}
