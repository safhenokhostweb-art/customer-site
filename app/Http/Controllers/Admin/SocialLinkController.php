<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;
use Illuminate\Http\Request;

class SocialLinkController extends Controller
{
    public function index()
    {
        $links = SocialLink::orderBy('order')->paginate(20);
        return view('admin.social-links.index', compact('links'));
    }

    public function create()
    {
        $platforms = SocialLink::platforms();
        return view('admin.social-links.create', compact('platforms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'platform' => 'required|string|in:' . implode(',', array_keys(SocialLink::platforms())),
            'url' => 'required|url|max:500',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $data['order'] = $data['order'] ?? SocialLink::max('order') + 1;
        $data['is_active'] = $request->boolean('is_active');
        SocialLink::create($data);
        return redirect()->route('admin.social-links.index')->with('success', 'لینک ذخیره شد.');
    }

    public function edit(SocialLink $socialLink)
    {
        $platforms = SocialLink::platforms();
        return view('admin.social-links.edit', compact('socialLink', 'platforms'));
    }

    public function update(Request $request, SocialLink $socialLink)
    {
        $data = $request->validate([
            'platform' => 'required|string|in:' . implode(',', array_keys(SocialLink::platforms())),
            'url' => 'required|url|max:500',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $socialLink->update($data);
        return redirect()->route('admin.social-links.index')->with('success', 'لینک به‌روزرسانی شد.');
    }

    public function destroy(SocialLink $socialLink)
    {
        $socialLink->delete();
        return redirect()->route('admin.social-links.index')->with('success', 'لینک حذف شد.');
    }
}
