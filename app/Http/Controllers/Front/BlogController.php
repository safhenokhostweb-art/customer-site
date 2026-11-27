<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\Post;
use App\Models\Category;
use App\Models\Page;
use App\Models\PostSeries;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        if (! SiteHelper::blogEnabled()) {
            abort(404);
        }
        $query = Post::query()
            ->published()
            ->with(['category', 'tags'])
            ->latest('published_at');

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', trim((string) $request->query('category')));
            });
        }

        if ($request->filled('tag')) {
            $slug = trim((string) $request->query('tag'));
            $query->whereHas('tags', fn ($q) => $q->where('slug', $slug));
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $query->where(function ($qb) use ($search) {
                $qb->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }
        $posts = $query->paginate(9)->withQueryString();
        $categories = Category::query()
            ->withCount(['posts as published_posts_count' => function ($q) {
                $q->published();
            }])
            ->orderBy('order')
            ->get();
        $popularPosts = Post::query()
            ->published()
            ->with('category')
            ->orderByDesc('view_count')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();
        $featuredPosts = Post::query()
            ->published()
            ->with('category')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();
        $popularTags = Tag::query()
            ->withCount(['posts as published_posts_count' => function ($q) {
                $q->published();
            }])
            ->having('published_posts_count', '>', 0)
            ->orderByDesc('published_posts_count')
            ->limit(24)
            ->get();
        $pages = Page::published()->menu()->get();

        if (! $request->hasAny(['q', 'category', 'tag'])) {
            if ($canvasView = app(\App\Http\Controllers\Front\ThemeCanvasPageController::class)->tryRenderCanvasPage('blog', 'وبلاگ')) {
                return $canvasView;
            }
        }

        return view(SiteHelper::view('blog.index'), compact('posts', 'categories', 'popularPosts', 'featuredPosts', 'popularTags', 'pages'));
    }

    public function category(Request $request, string $slug)
    {
        if (! SiteHelper::blogEnabled()) {
            abort(404);
        }
        $category = Category::where('slug', $slug)->firstOrFail();
        $query = Post::query()
            ->published()
            ->with(['category', 'tags'])
            ->where('category_id', $category->id)
            ->latest('published_at');
        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $query->where(function ($qb) use ($search) {
                $qb->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }
        $posts = $query->paginate(9)->withQueryString();
        $categories = Category::query()
            ->withCount(['posts as published_posts_count' => function ($q) {
                $q->published();
            }])
            ->orderBy('order')
            ->get();
        $popularPosts = Post::query()
            ->published()
            ->with('category')
            ->orderByDesc('view_count')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();
        $popularTags = Tag::query()
            ->withCount(['posts as published_posts_count' => function ($q) {
                $q->published();
            }])
            ->having('published_posts_count', '>', 0)
            ->orderByDesc('published_posts_count')
            ->limit(24)
            ->get();
        $pages = Page::published()->menu()->get();

        return view(SiteHelper::view('blog.index'), compact('posts', 'categories', 'popularPosts', 'popularTags', 'pages', 'category'));
    }

    public function series(Request $request, string $slug)
    {
        if (! SiteHelper::blogEnabled()) {
            abort(404);
        }
        $series = PostSeries::where('slug', $slug)->firstOrFail();
        $posts = $series->posts()
            ->published()
            ->with(['category', 'tags'])
            ->orderByRaw('part_number IS NULL, part_number ASC')
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();
        $pages = Page::published()->menu()->get();

        return view(SiteHelper::view('blog.series'), compact('series', 'posts', 'pages'));
    }

    public function show(Request $request, string $slug)
    {
        if (! SiteHelper::blogEnabled()) {
            abort(404);
        }
        $post = Post::query()
            ->published()
            ->with(['category', 'tags', 'series', 'user'])
            ->where('slug', $slug)
            ->firstOrFail();

        $viewKey = 'post_view_'.$post->id;
        if (! $request->session()->has($viewKey)) {
            $post->increment('view_count');
            $request->session()->put($viewKey, true);
        }

        $relatedPosts = Post::query()
            ->published()
            ->whereKeyNot($post->id)
            ->when($post->category_id, function ($q) use ($post) {
                $q->where('category_id', $post->category_id);
            })
            ->latest('published_at')
            ->limit(4)
            ->get();

        if ($relatedPosts->count() < 4) {
            $fallback = Post::query()
                ->published()
                ->whereKeyNot($post->id)
                ->whereNotIn('id', $relatedPosts->pluck('id'))
                ->orderByDesc('view_count')
                ->orderByDesc('published_at')
                ->limit(4 - $relatedPosts->count())
                ->get();
            $relatedPosts = $relatedPosts->concat($fallback);
        }

        $seriesSiblings = collect();
        if ($post->series_id) {
            $seriesSiblings = Post::query()
                ->published()
                ->where('series_id', $post->series_id)
                ->orderByRaw('part_number IS NULL, part_number ASC')
                ->get();
        }

        $pages = Page::published()->menu()->get();

        if ($canvasView = app(\App\Http\Controllers\Front\ThemeCanvasPageController::class)->tryRenderCanvasPage('post_detail', $post->title)) {
            return $canvasView;
        }

        return view(SiteHelper::view('blog.show'), compact('post', 'relatedPosts', 'seriesSiblings', 'pages'));
    }

    public function like(Request $request, string $slug): Response
    {
        if (! SiteHelper::blogEnabled()) {
            abort(404);
        }
        $post = Post::query()->published()->where('slug', $slug)->firstOrFail();
        $key = 'post_liked_'.$post->id;
        if ($request->session()->has($key)) {
            return response()->noContent();
        }
        $post->increment('likes_count');
        $request->session()->put($key, true);

        return response()->noContent();
    }
}
