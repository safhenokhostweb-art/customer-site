<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Models\PostSeries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostSeriesController extends Controller
{
    public function index()
    {
        $series = PostSeries::query()->withCount('posts')->orderBy('title')->get();

        return view('admin.post-series.index', compact('series'));
    }

    public function create()
    {
        return view('admin.post-series.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        $raw = ! empty($data['slug']) ? $data['slug'] : $data['title'];
        $data['slug'] = SlugHelper::unique($raw, fn (string $slug) => PostSeries::query()->where('slug', $slug)->exists());
        PostSeries::query()->create($data);

        return redirect()->route('admin.post-series.index')->with('success', 'مجموعه ذخیره شد.');
    }

    public function edit(PostSeries $post_series)
    {
        return view('admin.post-series.edit', ['series' => $post_series]);
    }

    public function update(Request $request, PostSeries $post_series)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('post_series', 'slug')->ignore($post_series->id)],
            'description' => 'nullable|string',
        ]);
        $post_series->update($data);

        return redirect()->route('admin.post-series.index')->with('success', 'مجموعه به‌روزرسانی شد.');
    }

    public function destroy(PostSeries $post_series)
    {
        $post_series->posts()->update(['series_id' => null, 'part_number' => null]);
        $post_series->delete();

        return redirect()->route('admin.post-series.index')->with('success', 'مجموعه حذف شد.');
    }
}
