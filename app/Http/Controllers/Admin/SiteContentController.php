<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Models\SiteContentCategory;
use App\Models\SiteContentStep;
use App\Services\ContentAiAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiteContentController extends Controller
{
    public function __construct(private ContentAiAssistantService $ai) {}

    // ── Index ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $type     = $request->get('type');
        $status   = $request->get('status');
        $search   = $request->get('q');

        $query = SiteContent::with(['category', 'author'])
            ->when($type,   fn ($q) => $q->where('type', $type))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->where('type', '!=', SiteContent::TYPE_HELP)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total'     => SiteContent::where('type', '!=', SiteContent::TYPE_HELP)->count(),
            'published' => SiteContent::where('type', '!=', SiteContent::TYPE_HELP)->where('status', 'published')->count(),
            'draft'     => SiteContent::where('type', '!=', SiteContent::TYPE_HELP)->where('status', 'draft')->count(),
            'ai'        => SiteContent::where('ai_generated', true)->count(),
        ];

        return view('admin.site-content.index', compact('query', 'stats'));
    }

    // ── Create ────────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $type       = $request->get('type', SiteContent::TYPE_ARTICLE);
        $categories = SiteContentCategory::orderBy('name')->get();
        return view('admin.site-content.create', compact('type', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'        => 'required|in:' . implode(',', array_keys(SiteContent::$TYPES)),
            'title'       => 'required|string|max:255',
            'excerpt'     => 'nullable|string|max:500',
            'body'        => 'nullable|string',
            'status'      => 'required|in:draft,published,archived',
            'category_id' => 'nullable|exists:site_content_categories,id',
            'tags'        => 'nullable|string',
            'ai_generated'=> 'boolean',
            'ai_prompt'   => 'nullable|string',
            'steps'       => 'nullable|array',
            'steps.*.title' => 'required_with:steps|string',
            'steps.*.body'  => 'nullable|string',
        ]);

        $data['author_id']   = auth()->id();
        $data['tags']        = $data['tags'] ? array_map('trim', explode(',', $data['tags'])) : null;
        $data['published_at']= $data['status'] === 'published' ? now() : null;
        $steps               = $data['steps'] ?? [];
        unset($data['steps']);

        $content = SiteContent::create($data);

        if (!empty($steps)) {
            foreach ($steps as $i => $step) {
                SiteContentStep::create([
                    'content_id'  => $content->id,
                    'sort_order'  => $i + 1,
                    'title'       => $step['title'],
                    'body'        => $step['body'] ?? null,
                ]);
            }
        }

        return redirect()->route('admin.site-content.edit', $content)
            ->with('success', 'محتوا با موفقیت ذخیره شد.');
    }

    // ── Edit ──────────────────────────────────────────────────────────────

    public function edit(SiteContent $siteContent)
    {
        $siteContent->load('steps', 'category');
        $categories = SiteContentCategory::orderBy('name')->get();
        return view('admin.site-content.edit', compact('siteContent', 'categories'));
    }

    public function update(Request $request, SiteContent $siteContent)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'excerpt'     => 'nullable|string|max:500',
            'body'        => 'nullable|string',
            'status'      => 'required|in:draft,published,archived',
            'category_id' => 'nullable|exists:site_content_categories,id',
            'tags'        => 'nullable|string',
            'steps'       => 'nullable|array',
            'steps.*.title' => 'required_with:steps|string',
            'steps.*.body'  => 'nullable|string',
        ]);

        $data['tags'] = $data['tags'] ? array_map('trim', explode(',', $data['tags'])) : null;
        if ($data['status'] === 'published' && $siteContent->status !== 'published') {
            $data['published_at'] = now();
        }

        $steps = $data['steps'] ?? [];
        unset($data['steps']);

        $siteContent->update($data);

        if ($siteContent->hasSteps()) {
            $siteContent->steps()->delete();
            foreach ($steps as $i => $step) {
                SiteContentStep::create([
                    'content_id'  => $siteContent->id,
                    'sort_order'  => $i + 1,
                    'title'       => $step['title'],
                    'body'        => $step['body'] ?? null,
                ]);
            }
        }

        return redirect()->route('admin.site-content.edit', $siteContent)
            ->with('success', 'تغییرات ذخیره شد.');
    }

    // ── Delete ────────────────────────────────────────────────────────────

    public function destroy(SiteContent $siteContent)
    {
        $siteContent->delete();
        return redirect()->route('admin.site-content.index')
            ->with('success', 'محتوا حذف شد.');
    }

    // ── AI Generate ───────────────────────────────────────────────────────

    public function aiGenerate(Request $request)
    {
        $request->validate([
            'type'   => 'required|in:' . implode(',', array_keys(SiteContent::$TYPES)),
            'prompt' => 'required|string|min:5|max:500',
            'mode'   => 'in:full,steps',
        ]);

        $type   = $request->input('type');
        $prompt = $request->input('prompt');
        $mode   = $request->input('mode', 'full');

        if ($mode === 'steps') {
            $result = $this->ai->generateWithSteps($type, $prompt);

            // در صف قرار گرفت — worker لوکال پردازش می‌کند
            if (!$result['success'] && ($result['queued'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'queued'  => true,
                    'job_id'  => $result['job_id'],
                    'message' => $result['error'],
                ]);
            }

            if (!$result['success']) {
                return response()->json(['success' => false, 'error' => $result['error']], 422);
            }
            return response()->json(['success' => true, 'data' => $result['data'], 'mode' => 'steps']);
        }

        $result = $this->ai->generate($type, $prompt);

        // در صف قرار گرفت
        if (!$result['success'] && ($result['queued'] ?? false)) {
            return response()->json([
                'success' => false,
                'queued'  => true,
                'job_id'  => $result['job_id'],
                'message' => $result['error'],
            ]);
        }

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 422);
        }

        return response()->json([
            'success'  => true,
            'content'  => $result['content'],
            'provider' => $result['provider'] ?? 'ai',
            'mode'     => 'full',
        ]);
    }

    // ── Publish ───────────────────────────────────────────────────────────

    public function publish(SiteContent $siteContent)
    {
        $siteContent->update(['status' => 'published', 'published_at' => now()]);
        return back()->with('success', 'محتوا منتشر شد.');
    }
}
