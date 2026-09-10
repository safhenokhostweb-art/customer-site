<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CustomerMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerMediaController extends Controller
{
    public function __construct(private readonly CustomerMediaService $media)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'in:image,video,audio,document'],
        ]);

        $items = $this->media->paginateForCurrentSite($filters['q'] ?? null, $filters['category'] ?? null);
        $items->getCollection()->each(fn ($item) => $item->setAttribute('preview_url', $this->media->publicUrl($item)));

        return view('admin.media.index', compact('items', 'filters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file'],
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string'],
        ]);

        try {
            $this->media->upload($request->file('file'), $data);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return redirect()->route('admin.media.index')->with('success', 'رسانه با موفقیت بارگذاری شد.');
    }

    public function show(string $media): View
    {
        $item = $this->media->findForCurrentSite($media);
        $item->setAttribute('preview_url', $this->media->publicUrl($item));
        $usages = $this->media->usages($item);

        return view('admin.media.show', compact('item', 'usages'));
    }

    public function update(Request $request, string $media): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string'],
        ]);
        $item = $this->media->findForCurrentSite($media);
        $this->media->updateMetadata($item, $data);

        return redirect()->route('admin.media.show', $item->uuid)->with('success', 'اطلاعات رسانه به‌روزرسانی شد.');
    }

    public function destroy(string $media): RedirectResponse
    {
        $item = $this->media->findForCurrentSite($media);

        try {
            $this->media->deleteUnused($item);
        } catch (\LogicException $exception) {
            return redirect()->route('admin.media.index')->with('error', 'این رسانه در حال استفاده است و حذف نشد.');
        } catch (\RuntimeException $exception) {
            return redirect()->route('admin.media.index')->with('error', 'رکورد رسانه حذف شد، اما پاک‌سازی فایل ناموفق بود و باید دوباره تلاش شود.');
        }

        return redirect()->route('admin.media.index')->with('success', 'رسانه حذف شد.');
    }
}
