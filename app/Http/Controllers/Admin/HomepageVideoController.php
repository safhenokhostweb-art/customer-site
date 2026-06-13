<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageVideo;
use Illuminate\Http\Request;

class HomepageVideoController extends Controller
{
    public function index()
    {
        $videos = HomepageVideo::orderBy('sort_order')->orderBy('id')->get();
        return view('admin.homepage-videos.index', compact('videos'));
    }

    public function create()
    {
        return view('admin.homepage-videos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => 'nullable|string|max:255',
            'subtitle'        => 'nullable|string|max:500',
            'video_type'      => 'required|in:youtube,mp4,cloudflare',
            'video_url'       => 'required|string|max:1000',
            'thumbnail_url'   => 'nullable|string|max:500',
            'autoplay'        => 'boolean',
            'loop'            => 'boolean',
            'muted'           => 'boolean',
            'show_controls'   => 'boolean',
            'overlay_text'    => 'nullable|string|max:255',
            'overlay_opacity' => 'integer|min:0|max:100',
            'cta_text'        => 'nullable|string|max:100',
            'cta_url'         => 'nullable|string|max:500',
            'sort_order'      => 'integer|min:0',
            'is_active'       => 'boolean',
        ]);

        $data['autoplay']      = (bool) $request->input('autoplay', false);
        $data['loop']          = (bool) $request->input('loop', false);
        $data['muted']         = (bool) $request->input('muted', true);
        $data['show_controls'] = (bool) $request->input('show_controls', true);
        $data['is_active']     = (bool) $request->input('is_active', true);

        HomepageVideo::create($data);

        return redirect()->route('admin.homepage-videos.index')
            ->with('success', 'ویدیو با موفقیت اضافه شد.');
    }

    public function edit(HomepageVideo $homepageVideo)
    {
        return view('admin.homepage-videos.edit', ['video' => $homepageVideo]);
    }

    public function update(Request $request, HomepageVideo $homepageVideo)
    {
        $data = $request->validate([
            'title'           => 'nullable|string|max:255',
            'subtitle'        => 'nullable|string|max:500',
            'video_type'      => 'required|in:youtube,mp4,cloudflare',
            'video_url'       => 'required|string|max:1000',
            'thumbnail_url'   => 'nullable|string|max:500',
            'autoplay'        => 'boolean',
            'loop'            => 'boolean',
            'muted'           => 'boolean',
            'show_controls'   => 'boolean',
            'overlay_text'    => 'nullable|string|max:255',
            'overlay_opacity' => 'integer|min:0|max:100',
            'cta_text'        => 'nullable|string|max:100',
            'cta_url'         => 'nullable|string|max:500',
            'sort_order'      => 'integer|min:0',
            'is_active'       => 'boolean',
        ]);

        $data['autoplay']      = (bool) $request->input('autoplay', false);
        $data['loop']          = (bool) $request->input('loop', false);
        $data['muted']         = (bool) $request->input('muted', true);
        $data['show_controls'] = (bool) $request->input('show_controls', true);
        $data['is_active']     = (bool) $request->input('is_active', true);

        $homepageVideo->update($data);

        return redirect()->route('admin.homepage-videos.index')
            ->with('success', 'ویدیو با موفقیت ویرایش شد.');
    }

    public function destroy(HomepageVideo $homepageVideo)
    {
        $homepageVideo->delete();
        return redirect()->route('admin.homepage-videos.index')
            ->with('success', 'ویدیو حذف شد.');
    }

    public function reorder(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);
        foreach ($request->input('order') as $pos => $id) {
            HomepageVideo::where('id', $id)->update(['sort_order' => $pos]);
        }
        return response()->json(['ok' => true]);
    }
}
