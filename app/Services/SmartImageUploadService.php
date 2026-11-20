<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SmartImageUploadService
{
    /**
     * Context → [max_width, max_height, quality, mode, webp]
     * mode: cover (crop center), contain (fit with padding), fit (scale proportionally)
     */
    private const CONTEXTS = [
        'avatar'        => [120,  120,  88, 'cover',   true],
        'thumbnail'     => [300,  300,  82, 'cover',   true],
        'featured'      => [1200, 800,  85, 'contain', true],
        'product'       => [800,  800,  85, 'cover',   true],
        'course'        => [800,  600,  85, 'contain', true],
        'icon'          => [128,  128,  90, 'cover',   true],
        'messaging'     => [256,  256,  88, 'cover',   true],
        'advertisement' => [1200, 400,  88, 'cover',   true],
        'banner'        => [1920, 600,  85, 'cover',   true],
        'hero'          => [1920, 800,  87, 'cover',   true],
        'blog_cover'    => [1200, 630,  85, 'contain', true],
        'gallery'       => [1200, 900,  83, 'contain', true],
        'logo'          => [400,  200,  90, 'contain', false],
        'og_image'      => [1200, 630,  85, 'cover',   true],
        'video_thumb'   => [1280, 720,  85, 'cover',   true],
    ];

    private const DEFAULT_CONTEXT = 'thumbnail';

    public function __construct(private readonly MediaStorage $media)
    {
    }

    public function upload(UploadedFile $file, string $context = 'thumbnail'): array
    {
        // GAP-H07 — Storage limit enforcement
        $this->enforceStorageLimit($file->getSize());
        // Phase 4 — the platform's disk quota (local + object storage).
        app(MediaQuota::class)->assertCanStore((int) $file->getSize());

        if (!array_key_exists($context, self::CONTEXTS)) {
            $context = self::DEFAULT_CONTEXT;
        }

        $cfg      = self::CONTEXTS[$context];
        $maxW     = $cfg[0];
        $maxH     = $cfg[1];
        $quality  = $cfg[2];
        $mode     = $cfg[3];
        $useWebp  = $cfg[4];

        $tempPath = $file->getRealPath() ?: $file->getPathname();
        if (!$tempPath || !is_readable($tempPath)) {
            throw new \RuntimeException('فایل موقت آپلود قابل خواندن نیست.');
        }

        $originalSize = filesize($tempPath);
        $originalMime = $this->detectMime($tempPath, $file);

        // Try AI enhancement first (when LOCAL_AI_ENABLED + endpoint available)
        $aiEnhanced = false;
        $processedPath = $tempPath;
        if ($this->aiAvailable()) {
            $enhanced = $this->enhanceWithAi($tempPath, $context);
            if ($enhanced !== null) {
                $processedPath = $enhanced;
                $aiEnhanced = true;
            }
        }

        // Process with GD
        $imageData = $this->processImage($processedPath, $originalMime, $maxW, $maxH, $quality, $mode, $useWebp);

        // Cleanup AI temp file
        if ($aiEnhanced && $processedPath !== $tempPath && file_exists($processedPath)) {
            @unlink($processedPath);
        }

        if ($imageData === null) {
            // Fallback: store original as-is
            return $this->storeRaw($file, $context, $originalSize);
        }

        // Determine output extension
        $outputExt = ($useWebp && function_exists('imagewebp')) ? 'webp' : $this->extFromMime($originalMime);

        $dir      = $context . '/' . date('Y/m');
        $filename = Str::random(22) . '.' . $outputExt;
        $fullPath = $dir . '/' . $filename;

        $disk = $this->media->disk('uploads');
        $disk->makeDirectory($dir);
        if (! $disk->put($fullPath, $imageData['data'])) {
            throw new \RuntimeException('Image storage write failed.');
        }

        $finalSize   = strlen($imageData['data']);
        $reduction   = $originalSize > 0 ? round((1 - $finalSize / $originalSize) * 100) : 0;

        return [
            'path'          => $fullPath,
            'url'           => $this->media->url($fullPath, 'uploads'),
            'original_size' => $originalSize,
            'final_size'    => $finalSize,
            'size_reduction'=> max(0, $reduction) . '%',
            'width'         => $imageData['width'],
            'height'        => $imageData['height'],
            'format'        => $outputExt,
            'ai_enhanced'   => $aiEnhanced,
            'context'       => $context,
        ];
    }

    // ─── Private helpers ────────────────────────────────────────────────────

    /**
     * GAP-H07 — Reject upload if site storage limit would be exceeded.
     * Limit is read from Settings (set by platform); defaults to 512 MB.
     * Storage usage is calculated from the uploads disk and cached for 5 minutes.
     *
     * @throws \RuntimeException when storage limit would be exceeded
     */
    private function enforceStorageLimit(int $incomingBytes): void
    {
        $limitMb = (int) Setting::get('storage_limit_mb', 512);
        if ($limitMb <= 0) {
            return;
        }

        $limitBytes = $limitMb * 1024 * 1024;

        $usedBytes = Cache::remember('storage_used_bytes', 300, function (): int {
            $disk  = $this->media->disk('uploads');
            $total = 0;
            foreach ($disk->allFiles() as $file) {
                $total += $disk->size($file);
            }
            return $total;
        });

        if (($usedBytes + $incomingBytes) > $limitBytes) {
            $usedMb    = round($usedBytes / 1024 / 1024, 1);
            $incomingKb = round($incomingBytes / 1024, 0);
            throw new \RuntimeException(
                "محدودیت فضای ذخیره‌سازی ({$limitMb} MB) پر شده است. "
                . "فضای استفاده‌شده: {$usedMb} MB. "
                . "برای افزایش فضا با پشتیبانی تماس بگیرید."
            );
        }
    }

    private function processImage(
        string $path,
        string $mime,
        int $maxW,
        int $maxH,
        int $quality,
        string $mode,
        bool $useWebp
    ): ?array {
        $src = $this->loadImage($path, $mime);
        if ($src === null) {
            return null;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        [$dstW, $dstH, $srcX, $srcY, $srcW, $srcH] = $this->calcGeometry(
            $origW, $origH, $maxW, $maxH, $mode
        );

        $dst = imagecreatetruecolor($dstW, $dstH);
        if (!$dst) {
            imagedestroy($src);
            return null;
        }

        // PNG transparency
        if ($mime === 'image/png') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $bg = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $bg);
        }

        // White background for contain mode
        if ($mode === 'contain' && $mime !== 'image/png') {
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $white);
        }

        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($src);

        // BASELINE ZERO PASS: these GD functions write the encoded image
        // straight to the output buffer when given a null "to file" arg —
        // ob_start()/ob_get_clean() around them is how that gets captured
        // as a string instead of echoed. Without try/finally, any warning
        // PHPUnit's error handler promotes to an exception (or any other
        // exception from a malformed/edge-case source image) skips
        // ob_get_clean() entirely and leaves the buffer open for the rest
        // of the request — PHPUnit's "did not close its own output
        // buffers" risky-test warning on tests exercising real image
        // rendering paths (shop/blog product images).
        $obLevel = ob_get_level();
        ob_start();
        try {
            $canWebp = $useWebp && function_exists('imagewebp');
            if ($canWebp) {
                imagewebp($dst, null, $quality);
            } elseif ($mime === 'image/png') {
                // PNG quality: 0-9 (invert: 9 = max compression)
                $pngQ = (int) round((100 - $quality) / 10);
                imagepng($dst, null, max(0, min(9, $pngQ)));
            } elseif ($mime === 'image/gif') {
                imagegif($dst);
            } else {
                imagejpeg($dst, null, $quality);
            }
            $data = ob_get_clean();
        } finally {
            // Guarantee the buffer this method opened is always closed,
            // even if one of the image* calls above threw.
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }
        }
        imagedestroy($dst);

        if (!$data) {
            return null;
        }

        return ['data' => $data, 'width' => $dstW, 'height' => $dstH];
    }

    private function calcGeometry(int $origW, int $origH, int $maxW, int $maxH, string $mode): array
    {
        if ($mode === 'cover') {
            // Crop center to fill maxW×maxH
            $cropAspect = $maxW / $maxH;
            $origAspect = $origW / $origH;
            if ($origAspect > $cropAspect) {
                $srcH = $origH;
                $srcW = (int) round($origH * $cropAspect);
                $srcX = (int) round(($origW - $srcW) / 2);
                $srcY = 0;
            } else {
                $srcW = $origW;
                $srcH = (int) round($origW / $cropAspect);
                $srcX = 0;
                $srcY = (int) round(($origH - $srcH) / 2);
            }
            return [$maxW, $maxH, $srcX, $srcY, $srcW, $srcH];
        }

        if ($mode === 'contain') {
            // Scale proportionally to fit within maxW×maxH, no crop
            $scale = min($maxW / $origW, $maxH / $origH);
            if ($scale >= 1) {
                $scale = 1; // Never upscale
            }
            $dstW = (int) round($origW * $scale);
            $dstH = (int) round($origH * $scale);
            return [$dstW, $dstH, 0, 0, $origW, $origH];
        }

        // fit: scale to fit width, proportional height (no crop, no padding)
        $scale = $origW > $maxW ? $maxW / $origW : 1;
        $dstW  = (int) round($origW * $scale);
        $dstH  = (int) round($origH * $scale);
        if ($dstH > $maxH) {
            $scale = $maxH / $origH;
            $dstW  = (int) round($origW * $scale);
            $dstH  = (int) round($origH * $scale);
        }
        return [$dstW, $dstH, 0, 0, $origW, $origH];
    }

    private function loadImage(string $path, string $mime): ?\GdImage
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path) ?: null,
            'image/png'               => @imagecreatefrompng($path) ?: null,
            'image/gif'               => @imagecreatefromgif($path) ?: null,
            'image/webp'              => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            default                   => null,
        };
    }

    private function detectMime(string $path, UploadedFile $file): string
    {
        $info = @getimagesize($path);
        if ($info && isset($info['mime'])) {
            return $info['mime'];
        }
        return $file->getMimeType() ?? 'image/jpeg';
    }

    private function extFromMime(string $mime): string
    {
        return match ($mime) {
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
    }

    private function storeRaw(UploadedFile $file, string $context, int $originalSize): array
    {
        $dir      = $context . '/' . date('Y/m');
        $ext      = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $filename = Str::random(22) . '.' . $ext;
        $fullPath = $dir . '/' . $filename;

        $uploadDisk = $this->media->diskNameFor('uploads');
        $disk       = Storage::disk($uploadDisk);
        $disk->makeDirectory($dir);
        if ($file->storeAs($dir, $filename, $uploadDisk) === false) {
            throw new \RuntimeException('Image storage write failed.');
        }

        return [
            'path'          => $fullPath,
            'url'           => $this->media->url($fullPath, 'uploads'),
            'original_size' => $originalSize,
            'final_size'    => $originalSize,
            'size_reduction'=> '0%',
            'width'         => 0,
            'height'        => 0,
            'format'        => $ext,
            'ai_enhanced'   => false,
            'context'       => $context,
        ];
    }

    // ─── AI enhancement (optional) ──────────────────────────────────────────

    private function aiAvailable(): bool
    {
        return (bool) config('services.local_ai.enabled', false);
    }

    private function enhanceWithAi(string $path, string $context): ?string
    {
        $endpoint = rtrim((string) config('services.local_ai.base_url', ''), '/');
        if ($endpoint === '') {
            return null;
        }

        $enhanceUrl = $endpoint . '/image/enhance';

        try {
            $response = Http::timeout(15)
                ->attach('image', file_get_contents($path), 'image.jpg')
                ->post($enhanceUrl, ['context' => $context]);

            if (!$response->successful()) {
                return null;
            }

            $body = $response->body();
            if (!$body || strlen($body) < 1000) {
                return null;
            }

            // Save to temp file
            $tmpFile = sys_get_temp_dir() . '/ai_enhanced_' . Str::random(16) . '.jpg';
            file_put_contents($tmpFile, $body);
            return $tmpFile;
        } catch (\Throwable) {
            return null;
        }
    }

    // ─── Static helpers ─────────────────────────────────────────────────────

    public static function contextList(): array
    {
        return array_keys(self::CONTEXTS);
    }

    public static function contextHint(string $context): string
    {
        $cfg = self::CONTEXTS[$context] ?? self::CONTEXTS[self::DEFAULT_CONTEXT];
        return $cfg[0] . '×' . $cfg[1];
    }
}
