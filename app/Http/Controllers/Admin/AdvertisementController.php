<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    public function index(Request $request)
    {
        $query = Advertisement::query();
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }
        $advertisements = $query->orderBy('position')->orderBy('order')->paginate(20);

        $stats = [
            'total' => \App\Models\Advertisement::count(),
            'active' => \App\Models\Advertisement::query()->where('is_active', true)->count(),
            'inactive' => \App\Models\Advertisement::query()->where('is_active', false)->count(),
        ];

        return view('admin.advertisements.index', compact('advertisements', 'stats'));
    }

    public function create()
    {
        return view('admin.advertisements.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:banner,scrolling_text,typewriter,marquee,blink,fade',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'image_path' => 'nullable|string|max:500',
            'link' => 'nullable|string|max:500',
            'position' => 'required|in:header,footer,sidebar,content_top,content_bottom',
            'order' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|string',
            'expires_at' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (!empty($data['starts_at'])) {
            $data['starts_at'] = \App\Helpers\Jalali::toCarbon($data['starts_at']);
        }
        if (!empty($data['expires_at'])) {
            $data['expires_at'] = \App\Helpers\Jalali::toCarbon($data['expires_at']);
        }
        $data['is_active'] = $request->boolean('is_active', true);
        $data['order'] = (int) ($data['order'] ?? 0);

        Advertisement::create($data);
        return redirect()->route('admin.advertisements.index')->with('success', 'تبلیغ با موفقیت ایجاد شد.');
    }

    public function edit(Advertisement $advertisement)
    {
        return view('admin.advertisements.edit', compact('advertisement'));
    }

    public function update(Request $request, Advertisement $advertisement)
    {
        $data = $request->validate([
            'type' => 'required|in:banner,scrolling_text,typewriter,marquee,blink,fade',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'image_path' => 'nullable|string|max:500',
            'link' => 'nullable|string|max:500',
            'position' => 'required|in:header,footer,sidebar,content_top,content_bottom',
            'order' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|string',
            'expires_at' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (!empty($data['starts_at'])) {
            $data['starts_at'] = \App\Helpers\Jalali::toCarbon($data['starts_at']);
        } else {
            $data['starts_at'] = null;
        }
        if (!empty($data['expires_at'])) {
            $data['expires_at'] = \App\Helpers\Jalali::toCarbon($data['expires_at']);
        } else {
            $data['expires_at'] = null;
        }
        $data['is_active'] = $request->boolean('is_active', true);
        $data['order'] = (int) ($data['order'] ?? 0);

        $advertisement->update($data);
        return redirect()->route('admin.advertisements.index')->with('success', 'تبلیغ به‌روزرسانی شد.');
    }

    public function destroy(Advertisement $advertisement)
    {
        $advertisement->delete();
        return redirect()->route('admin.advertisements.index')->with('success', 'تبلیغ حذف شد.');
    }
}
