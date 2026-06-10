<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\Request;

class SiteContentController extends Controller
{
    public function index(Request $request)
    {
        $type   = $request->get('type');
        $search = $request->get('q');

        $contents = SiteContent::published()
            ->where('type', '!=', SiteContent::TYPE_HELP)
            ->when($type,   fn ($q) => $q->where('type', $type))
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->with('category')
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $typeCounts = SiteContent::published()
            ->where('type', '!=', SiteContent::TYPE_HELP)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return view('themes.default.content.index', compact('contents', 'type', 'typeCounts'));
    }

    public function show(string $slug)
    {
        $content = SiteContent::published()
            ->where('slug', $slug)
            ->where('type', '!=', SiteContent::TYPE_HELP)
            ->with(['steps', 'category', 'author'])
            ->firstOrFail();

        $related = SiteContent::published()
            ->where('type', $content->type)
            ->where('id', '!=', $content->id)
            ->latest('published_at')
            ->limit(4)
            ->get();

        return view('themes.default.content.show', compact('content', 'related'));
    }
}
