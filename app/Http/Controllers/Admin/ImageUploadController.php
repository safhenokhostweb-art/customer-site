<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /** سایزهای پیش‌فرض بر اساس context */
    public const SIZES = [
        'avatar' => [100, 100],
        'thumbnail' => [200, 200],
        'featured' => [800, 600],
        'product' => [400, 400],
        'course' => [400, 300],
        'icon' => [64, 64],
        'messaging' => [128, 128],
        'advertisement' => [1200, 400],
    ];

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
            'context' => 'nullable|string|in:avatar,thumbnail,featured,product,course,icon,messaging,advertisement',
        ]);
        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            return response()->json(['message' => 'فایل تصویر معتبر ارسال نشده است.'], 422);
        }
        $context = (string) $request->input('context', 'thumbnail');
        $root = (string) config('filesystems.disks.uploads.root', '');
        if ($root === '') {
            return response()->json(['message' => 'مسیر دیسک uploads در config/filesystems.php تنظیم نشده است.'], 500);
        }
        File::ensureDirectoryExists($root);

        try {
            $path = $this->processAndStore($file, $context);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => config('app.debug') ? $e->getMessage() : 'خطا در ذخیرهٔ تصویر.',
            ], 500);
        }
        $url = str_starts_with($path, 'http') ? $path : asset('uploads/' . ltrim($path, '/'));
        return response()->json([
            'url' => $url,
            'path' => $path,
        ]);
    }

    protected function processAndStore($file, string $context): string
    {
        $usePublicUploads = true;
        $dir = $context . '/' . date('Y/m');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '') {
            $ext = match ($file->getMimeType()) {
                'image/jpeg', 'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg',
            };
        }
        $filename = Str::random(20) . '.' . $ext;

        if ($usePublicUploads) {
            $disk = Storage::disk('uploads');
            $disk->makeDirectory($dir);
            $fullPath = $dir . '/' . $filename;
            $tempPath = $file->getRealPath();
            if (! is_string($tempPath) || $tempPath === '') {
                $tempPath = $file->getPathname();
            }
            if ($tempPath === '' || ! is_readable($tempPath)) {
                throw new \RuntimeException('امکان خواندن فایل موقت آپلود وجود ندارد (مسیر نامعتبر).');
            }
            $sizes = self::SIZES[$context] ?? self::SIZES['thumbnail'];
            $resized = $this->resizeImage($tempPath, $sizes[0], $sizes[1]);
            if ($resized) {
                $disk->put($fullPath, $resized);
            } else {
                $file->storeAs($dir, $filename, 'uploads');
            }
            return $fullPath;
        }

        if ($context === 'advertisement') {
            $path = $file->store('advertisements', 'public');
            return $path;
        }
        $sizes = self::SIZES[$context] ?? self::SIZES['thumbnail'];
        $width = $sizes[0];
        $height = $sizes[1];
        $dir = 'uploads/' . $context . '/' . date('Y/m');
        $fullPath = $dir . '/' . $filename;
        $tempPath = $file->getRealPath();
        if (! is_string($tempPath) || $tempPath === '') {
            $tempPath = $file->getPathname();
        }
        if ($tempPath === '' || ! is_readable($tempPath)) {
            throw new \RuntimeException('امکان خواندن فایل موقت آپلود وجود ندارد (مسیر نامعتبر).');
        }
        $resized = $this->resizeImage($tempPath, $width, $height);
        if ($resized) {
            Storage::disk('public')->put($fullPath, $resized);
        } else {
            $file->storeAs($dir, $filename, 'public');
        }
        return $fullPath;
    }

    protected function resizeImage(string $path, int $maxWidth, int $maxHeight): ?string
    {
        $info = @getimagesize($path);
        if (!$info) {
            return null;
        }
        $mime = $info['mime'] ?? '';
        $src = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
        if (!$src) {
            return null;
        }
        $origW = imagesx($src);
        $origH = imagesy($src);
        // cover: crop center + resize to fill target (هم‌اندازه با محل استفاده)
        $cropAspect = $maxWidth / $maxHeight;
        $origAspect = $origW / $origH;
        if ($origAspect > $cropAspect) {
            $srcW = (int) round($origH * $cropAspect);
            $srcH = $origH;
            $srcX = (int) round(($origW - $srcW) / 2);
            $srcY = 0;
        } else {
            $srcW = $origW;
            $srcH = (int) round($origW / $cropAspect);
            $srcX = 0;
            $srcY = (int) round(($origH - $srcH) / 2);
        }
        $dst = imagecreatetruecolor($maxWidth, $maxHeight);
        if (!$dst) {
            imagedestroy($src);
            return null;
        }
        if ($mime === 'image/png') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $maxWidth, $maxHeight, $transparent);
        }
        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $maxWidth, $maxHeight, $srcW, $srcH);
        imagedestroy($src);
        ob_start();
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($dst, null, 85);
                break;
            case 'image/png':
                imagepng($dst, null, 8);
                break;
            case 'image/gif':
                imagegif($dst);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    imagewebp($dst, null, 85);
                } else {
                    imagejpeg($dst, null, 85);
                }
                break;
            default:
                imagejpeg($dst, null, 85);
        }
        $data = ob_get_clean();
        imagedestroy($dst);
        return $data ?: null;
    }
}
