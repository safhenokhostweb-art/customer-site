<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerMedia;
use App\Services\CustomerMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerMediaController extends Controller
{
    public function __construct(private readonly CustomerMediaService $media)
    {
    }

    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'in:image,video,audio,document'],
            'usage' => ['nullable', 'in:used,unused'],
            'sort' => ['nullable', 'in:newest,oldest,name,size'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $items = $this->media->paginateForCurrentSite(
            $filters['q'] ?? null,
            $filters['category'] ?? null,
            $filters['usage'] ?? null,
            $filters['sort'] ?? 'newest',
        );
        $items->getCollection()->each(fn ($item) => $item->setAttribute('preview_url', $this->media->publicUrl($item)));

        if ($request->wantsJson()) {
            return response()->json([
                'items' => $items->getCollection()->map(fn (CustomerMedia $item) => $this->itemPayload($item))->values(),
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                ],
            ]);
        }

        return view('admin.media.index', compact('items', 'filters'));
    }

    /**
     * Multi-file upload. Each file is validated and stored independently
     * through the SAME canonical CustomerMediaService::upload() call the
     * single-file path always used — one bad file in the batch never
     * aborts its siblings, and nothing here bypasses the service's own
     * MIME/size policy or its transactional rollback-on-DB-failure.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $files = $request->hasFile('files') ? $request->file('files') : ($request->hasFile('file') ? [$request->file('file')] : []);
        if (empty($files)) {
            throw ValidationException::withMessages(['file' => 'حداقل یک فایل باید انتخاب شود.']);
        }

        $metadata = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:500'],
        ]);

        $results = [];
        foreach ($files as $file) {
            $name = $file->getClientOriginalName();
            try {
                $created = $this->media->upload($file, $metadata);
                $created->setAttribute('preview_url', $this->media->publicUrl($created));
                $results[] = ['file' => $name, 'status' => 'success', 'item' => $this->itemPayload($created)];
            } catch (ValidationException $exception) {
                $results[] = ['file' => $name, 'status' => 'failed', 'message' => collect($exception->errors())->flatten()->first()];
            } catch (\Throwable $exception) {
                $results[] = ['file' => $name, 'status' => 'failed', 'message' => 'بارگذاری فایل ناموفق بود.'];
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['results' => $results]);
        }

        $failed = collect($results)->where('status', 'failed')->count();
        $message = $failed === 0 ? 'رسانه با موفقیت بارگذاری شد.' : "برخی فایل‌ها بارگذاری نشدند ({$failed} مورد).";

        return redirect()->route('admin.media.index')->with($failed === 0 ? 'success' : 'error', $message);
    }

    public function show(Request $request, string $media): View|JsonResponse
    {
        $item = $this->media->findForCurrentSite($media);
        $item->setAttribute('preview_url', $this->media->publicUrl($item));
        $usages = $this->media->usages($item);

        if ($request->wantsJson()) {
            return response()->json(['item' => $this->itemPayload($item, detailed: true), 'usages' => $usages->map(fn ($u) => [
                'consumer_type' => $u->consumer_type, 'reference_path' => $u->reference_path,
            ])->values()]);
        }

        return view('admin.media.show', compact('item', 'usages'));
    }

    public function update(Request $request, string $media): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $item = $this->media->findForCurrentSite($media);
        $item = $this->media->updateMetadata($item, $data);
        $item->setAttribute('preview_url', $this->media->publicUrl($item));

        if ($request->wantsJson()) {
            return response()->json(['item' => $this->itemPayload($item, detailed: true)]);
        }

        return redirect()->route('admin.media.show', $item->uuid)->with('success', 'اطلاعات رسانه به‌روزرسانی شد.');
    }

    public function replace(Request $request, string $media): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['file' => ['required', 'file']]);
        $item = $this->media->findForCurrentSite($media);

        try {
            $item = $this->media->replace($item, $data['file']);
        } catch (ValidationException $exception) {
            if ($request->wantsJson()) {
                return response()->json(['message' => collect($exception->errors())->flatten()->first()], 422);
            }
            throw $exception;
        }
        $item->setAttribute('preview_url', $this->media->publicUrl($item));

        if ($request->wantsJson()) {
            return response()->json(['item' => $this->itemPayload($item, detailed: true)]);
        }

        return redirect()->route('admin.media.show', $item->uuid)->with('success', 'فایل رسانه جایگزین شد.');
    }

    public function editImage(Request $request, string $media): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'operation' => ['required', 'in:rotate,crop,resize'],
            'degrees' => ['nullable', 'integer'],
            'x' => ['nullable', 'integer', 'min:0'],
            'y' => ['nullable', 'integer', 'min:0'],
            'width' => ['nullable', 'integer', 'min:1'],
            'height' => ['nullable', 'integer', 'min:1'],
            'max_width' => ['nullable', 'integer', 'min:1', 'max:8000'],
            'max_height' => ['nullable', 'integer', 'min:1', 'max:8000'],
        ]);
        $item = $this->media->findForCurrentSite($media);

        try {
            $item = $this->media->editImage($item, $data['operation'], $data);
        } catch (ValidationException $exception) {
            if ($request->wantsJson()) {
                return response()->json(['message' => collect($exception->errors())->flatten()->first()], 422);
            }
            throw $exception;
        }
        $item->setAttribute('preview_url', $this->media->publicUrl($item));

        if ($request->wantsJson()) {
            return response()->json(['item' => $this->itemPayload($item, detailed: true)]);
        }

        return redirect()->route('admin.media.show', $item->uuid)->with('success', 'تصویر ویرایش شد.');
    }

    public function destroy(Request $request, string $media): RedirectResponse|JsonResponse
    {
        $item = $this->media->findForCurrentSite($media);

        try {
            $this->media->deleteUnused($item);
        } catch (\LogicException $exception) {
            $message = 'این رسانه در حال استفاده است و حذف نشد.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->route('admin.media.index')->with('error', $message);
        } catch (\RuntimeException $exception) {
            $message = 'رکورد رسانه حذف شد، اما پاک‌سازی فایل ناموفق بود و باید دوباره تلاش شود.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 200);
            }
            return redirect()->route('admin.media.index')->with('error', $message);
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => 'deleted']);
        }

        return redirect()->route('admin.media.index')->with('success', 'رسانه حذف شد.');
    }

    /**
     * Itemized batch delete: every uuid is resolved and deleted
     * independently through the SAME canonical deleteUnused() the
     * single-item delete uses. A used or invalid item never blocks its
     * siblings — the response reports a per-item outcome instead of an
     * all-or-nothing transaction, matching the delete-safety contract
     * (usage-ledger authority, one item at a time).
     */
    public function batchDestroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uuids' => ['required', 'array', 'min:1', 'max:100'],
            'uuids.*' => ['string'],
        ]);

        $results = [];
        foreach ($data['uuids'] as $uuid) {
            try {
                $item = $this->media->findForCurrentSite($uuid);
            } catch (\Throwable $exception) {
                $results[] = ['uuid' => $uuid, 'status' => 'failed', 'message' => 'رسانه یافت نشد.'];
                continue;
            }
            try {
                $this->media->deleteUnused($item);
                $results[] = ['uuid' => $uuid, 'status' => 'deleted'];
            } catch (\LogicException $exception) {
                $results[] = ['uuid' => $uuid, 'status' => 'blocked', 'message' => 'در حال استفاده است.'];
            } catch (\Throwable $exception) {
                $results[] = ['uuid' => $uuid, 'status' => 'failed', 'message' => 'حذف ناموفق بود.'];
            }
        }

        return response()->json(['results' => $results]);
    }

    private function itemPayload(CustomerMedia $item, bool $detailed = false): array
    {
        $payload = [
            'uuid' => $item->uuid,
            'title' => $item->title,
            'original_filename' => $item->original_filename,
            'category' => $item->category,
            'mime_type' => $item->mime_type,
            'size_bytes' => $item->size_bytes,
            'width' => $item->width,
            'height' => $item->height,
            'preview_url' => $item->preview_url,
            'usages_count' => $item->usages_count ?? null,
            'created_at' => $item->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $payload['alt_text'] = $item->alt_text;
            $payload['caption'] = $item->caption;
            $payload['description'] = $item->description;
        }

        return $payload;
    }
}
