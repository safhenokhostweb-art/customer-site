<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CustomerMediaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * MEDIA-4: compatibility adapter between the Theme Builder's media picker
 * and the canonical Core Media Library. This is not a second writer —
 * every mutation delegates to CustomerMediaService (the sole customer-media
 * write authority); current-site identity, MIME/size policy, and delete
 * protection are all resolved there, never re-implemented here and never
 * accepted from the client.
 */
class ThemeDesignMediaController extends Controller
{
    public function __construct(private readonly CustomerMediaService $media)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'in:image,video,audio,document'],
            'uuid' => ['nullable', 'string'],
        ]);

        // A saved {media_id} value needs to resolve to a display URL for
        // the property panel's own preview after a fresh page load, before
        // the library list has ever been fetched — a single, current-site-
        // scoped lookup, not a second read path (still CustomerMediaService).
        if (! empty($filters['uuid'])) {
            try {
                $item = $this->media->findForCurrentSite($filters['uuid']);
            } catch (ModelNotFoundException) {
                return response()->json(['images' => []]);
            }

            return response()->json(['images' => [[
                'uuid' => $item->uuid,
                'url' => $this->media->publicUrl($item),
                'name' => $item->original_filename,
                'title' => $item->title,
                'category' => $item->category,
                'size' => $item->size_bytes,
            ]]]);
        }

        $items = $this->media->paginateForCurrentSite($filters['q'] ?? null, $filters['category'] ?? null, perPage: 24);

        $images = $items->getCollection()->map(fn ($item) => [
            'uuid' => $item->uuid,
            'url' => $this->media->publicUrl($item),
            'name' => $item->original_filename,
            'title' => $item->title,
            'category' => $item->category,
            'size' => $item->size_bytes,
        ])->values();

        return response()->json([
            'images' => $images,
            'has_more' => $items->hasMorePages(),
            'current_page' => $items->currentPage(),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:10240']]);

        $file = $request->file('file');
        if (! $file || ! $file->isValid()) {
            return response()->json(['error' => 'فایل آپلود شده نامعتبر است یا با خطا مواجه شد.'], 422);
        }

        $tempPath = $file->getRealPath() ?: $file->getPathname();
        if (empty($tempPath) || ! file_exists($tempPath) || ! is_readable($tempPath)) {
            return response()->json(['error' => 'مسیر موقت فایل آپلود شده در دسترس نیست.'], 422);
        }

        try {
            $media = $this->media->upload($file, [
                'title' => $request->input('title'),
            ]);
        } catch (ValidationException $exception) {
            return response()->json(['error' => collect($exception->errors())->flatten()->first() ?? 'خطا در بارگذاری فایل'], 422);
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error('Media upload exception', ['message' => $exception->getMessage()]);
            return response()->json(['error' => 'خطا در ذخیره‌سازی فایل: ' . $exception->getMessage()], 500);
        }

        return response()->json([
            'uuid' => $media->uuid,
            'url' => $this->media->publicUrl($media),
            'name' => $media->original_filename,
        ]);
    }

    /**
     * Kept as a thin, protected delegate for compatibility. The picker UI
     * no longer offers deletion itself — the main Media Library
     * (وبسایت → کتابخانه رسانه) is the one place delete is exposed to
     * admins, so there is exactly one delete authority in the UX too.
     */
    public function destroy(Request $request): JsonResponse
    {
        $uuid = (string) $request->input('uuid', '');
        if ($uuid === '') {
            return response()->json(['error' => 'شناسه رسانه نامعتبر است'], 422);
        }

        try {
            $media = $this->media->findForCurrentSite($uuid);
            $this->media->deleteUnused($media);
        } catch (ModelNotFoundException) {
            return response()->json(['error' => 'رسانه یافت نشد'], 404);
        } catch (\LogicException) {
            return response()->json(['error' => 'این رسانه در حال استفاده است و حذف نشد.'], 422);
        }

        return response()->json(['ok' => true]);
    }
}
