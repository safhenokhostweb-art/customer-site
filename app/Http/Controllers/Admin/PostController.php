<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Category;
use App\Models\PostSeries;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::query()
            ->with(['category', 'tags'])
            ->when(request()->filled('q'), function ($q) {
                $search = trim((string) request('q'));
                $q->where(function ($qb) use ($search) {
                    $qb->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%");
                });
            })
            ->when(request()->filled('status'), function ($q) {
                $status = (string) request('status');
                if ($status === 'draft') {
                    $q->where('is_published', false)->whereIn('editorial_status', [Post::EDITORIAL_DRAFT, Post::EDITORIAL_REJECTED]);
                } elseif ($status === 'pending_review') {
                    $q->where('editorial_status', Post::EDITORIAL_PENDING_REVIEW)->where('is_published', false);
                } elseif ($status === 'approved') {
                    $q->where('editorial_status', Post::EDITORIAL_APPROVED)->where('is_published', false);
                } elseif ($status === 'rejected') {
                    $q->where('editorial_status', Post::EDITORIAL_REJECTED)->where('is_published', false);
                } elseif ($status === 'scheduled') {
                    $q->where('is_published', true)
                        ->where('editorial_status', Post::EDITORIAL_PUBLISHED)
                        ->where('published_at', '>', now());
                } elseif ($status === 'published') {
                    $q->where('is_published', true)
                        ->where('editorial_status', Post::EDITORIAL_PUBLISHED)
                        ->where(function ($qb) {
                            $qb->whereNull('published_at')->orWhere('published_at', '<=', now());
                        });
                }
            })
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.posts.index', compact('posts'));
    }

    public function create()
    {
        $categories = Category::orderBy('order')->get();
        $seriesList = PostSeries::orderBy('title')->get();

        return view('admin.posts.create', compact('categories', 'seriesList'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedPostData($request);
        $data['user_id'] = $request->user()->id;

        $this->hydrateEditorialFields($request, $data);

        $rawSlug = ! empty($data['slug']) ? $data['slug'] : $data['title'];
        $data['slug'] = SlugHelper::unique($rawSlug, fn (string $slug) => Post::query()->where('slug', $slug)->exists());

        if (! SlugHelper::isValid($data['slug'])) {
            return back()->withErrors(['slug' => 'نامک واردشده معتبر نیست.'])->withInput();
        }

        $this->fillSeoDefaults($data);

        $post = Post::create($data);
        $this->syncPostTags($post, $request->input('tags_input'));

        return redirect()->route('admin.posts.index')->with('success', $this->storeSuccessMessage($data['save_action'] ?? 'draft'));
    }

    public function edit(Post $post)
    {
        $categories = Category::orderBy('order')->get();
        $seriesList = PostSeries::orderBy('title')->get();
        $tagsInput = $post->tags->pluck('name')->implode('، ');

        return view('admin.posts.edit', compact('post', 'categories', 'seriesList', 'tagsInput'));
    }

    public function update(Request $request, Post $post)
    {
        $this->maybeRecordRevision($request, $post);

        $data = $this->validatedPostData($request, $post);
        $this->hydrateEditorialFields($request, $data, $post);

        $rawSlug = ! empty($data['slug']) ? $data['slug'] : $data['title'];
        $data['slug'] = SlugHelper::unique($rawSlug, fn (string $slug) => Post::query()->where('slug', $slug)->whereKeyNot($post->id)->exists());

        if (! SlugHelper::isValid($data['slug'])) {
            return back()->withErrors(['slug' => 'نامک واردشده معتبر نیست.'])->withInput();
        }

        $this->fillSeoDefaults($data);

        $post->update($data);
        $this->syncPostTags($post, $request->input('tags_input'));

        return redirect()->route('admin.posts.index')->with('success', $this->updateSuccessMessage($data['save_action'] ?? 'draft'));
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'مطلب حذف شد.');
    }

    public function revisions(Post $post)
    {
        $revisions = $post->revisions()->with('user')->paginate(20);

        return view('admin.posts.revisions', compact('post', 'revisions'));
    }

    public function restoreRevision(Request $request, Post $post, PostRevision $revision)
    {
        if ($revision->post_id !== $post->id) {
            abort(404);
        }

        $snap = $revision->snapshot ?? [];
        $tagIds = $snap['tag_ids'] ?? [];
        unset($snap['tag_ids']);

        foreach ($snap as $key => $value) {
            if (! in_array($key, (new Post)->getFillable(), true)) {
                continue;
            }
            if ($key === 'published_at' && $value !== null) {
                $post->{$key} = Carbon::parse($value);

                continue;
            }
            $post->{$key} = $value;
        }
        $post->save();
        if (is_array($tagIds)) {
            $post->tags()->sync($tagIds);
        }

        return redirect()->route('admin.posts.edit', $post)->with('success', 'نسخهٔ انتخاب‌شده بازگردانی شد.');
    }

    public function approve(Post $post)
    {
        if ($post->editorial_status !== Post::EDITORIAL_PENDING_REVIEW) {
            return back()->withErrors(['approve' => 'فقط مطالب در انتظار بازبینی قابل تایید هستند.']);
        }
        $post->update([
            'editorial_status' => Post::EDITORIAL_APPROVED,
            'is_published' => false,
            'published_at' => null,
        ]);

        return back()->with('success', 'مطلب تایید شد؛ اکنون می‌توانید آن را منتشر کنید.');
    }

    public function reject(Request $request, Post $post)
    {
        $request->validate([
            'reject_note' => 'nullable|string|max:500',
        ]);
        if ($post->editorial_status !== Post::EDITORIAL_PENDING_REVIEW) {
            return back()->withErrors(['reject' => 'فقط مطالب در انتظار بازبینی قابل رد هستند.']);
        }
        $post->update([
            'editorial_status' => Post::EDITORIAL_REJECTED,
            'is_published' => false,
            'published_at' => null,
        ]);

        return back()->with('success', 'مطلب رد شد.');
    }

    private function validatedPostData(Request $request, ?Post $post = null): array
    {
        $slugRule = Rule::unique('posts', 'slug');
        if ($post) {
            $slugRule = $slugRule->ignore($post->id);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', $slugRule],
            'body' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|string|max:500',
            'gallery_text' => 'nullable|string|max:8000',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500',
            'category_id' => 'nullable|exists:categories,id',
            'series_id' => 'nullable|exists:post_series,id',
            'part_number' => 'nullable|integer|min:1|max:65535',
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'save_action' => ['nullable', Rule::in(['draft', 'review', 'publish', 'schedule'])],
            'tags_input' => 'nullable|string|max:2000',
        ]);

        $lines = preg_split('/\r\n|\r|\n/', (string) ($data['gallery_text'] ?? '')) ?: [];
        $data['gallery_images'] = array_values(array_filter(array_map('trim', $lines), fn ($p) => $p !== ''));
        unset($data['gallery_text']);

        return $data;
    }

    private function fillSeoDefaults(array &$data): void
    {
        if (empty($data['excerpt']) && ! empty($data['body'])) {
            $data['excerpt'] = Post::query()->make($data)->previewText(220);
        }

        $data['meta_title'] = trim((string) ($data['meta_title'] ?? '')) ?: $data['title'];
        $data['meta_description'] = trim((string) ($data['meta_description'] ?? '')) ?: Post::query()->make($data)->previewText(160);
    }

    private function maybeRecordRevision(Request $request, Post $post): void
    {
        if (! $post->exists) {
            return;
        }
        PostRevision::query()->create([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'snapshot' => PostRevision::snapshotFromPost($post->fresh(['tags'])),
        ]);
    }

    private function syncPostTags(Post $post, ?string $tagsCsv): void
    {
        $tagsCsv = trim((string) $tagsCsv);
        if ($tagsCsv === '') {
            $post->tags()->sync([]);

            return;
        }
        $parts = preg_split('/[,،]/u', $tagsCsv) ?: [];
        $names = array_unique(array_filter(array_map('trim', $parts)));
        $ids = [];
        foreach ($names as $name) {
            if ($name === '') {
                continue;
            }
            $existing = Tag::query()->where('name', $name)->first();
            if ($existing) {
                $ids[] = $existing->id;

                continue;
            }
            $slug = SlugHelper::unique($name, fn (string $s) => Tag::query()->where('slug', $s)->exists());
            $tag = Tag::query()->create(['name' => $name, 'slug' => $slug]);
            $ids[] = $tag->id;
        }
        $post->tags()->sync($ids);
    }

    private function hydrateEditorialFields(Request $request, array &$data, ?Post $post = null): void
    {
        $action = (string) ($data['save_action'] ?? '');
        $publishedAt = ! empty($data['published_at']) ? Carbon::parse($data['published_at']) : null;
        $canModerate = $request->user()->hasPermission('posts_moderate');

        if (in_array($action, ['publish', 'schedule'], true) && ! $canModerate) {
            throw ValidationException::withMessages(['save_action' => 'شما مجوز انتشار یا زمان‌بندی مطلب ندارید. از مدیر بخواهید دسترسی «انتشار و تایید مطالب» بدهد.']);
        }

        if (in_array($action, ['publish', 'schedule'], true) && blank((string) ($data['body'] ?? ''))) {
            throw ValidationException::withMessages(['body' => 'برای انتشار یا زمان‌بندی، محتوای مطلب الزامی است.']);
        }

        if ($action === 'schedule' && ! $publishedAt) {
            throw ValidationException::withMessages(['published_at' => 'برای زمان‌بندی، تاریخ انتشار را وارد کنید.']);
        }

        if ($action === 'schedule' && $publishedAt && $publishedAt->lte(now()->addMinute())) {
            throw ValidationException::withMessages(['published_at' => 'تاریخ زمان‌بندی باید در آینده باشد.']);
        }

        if (in_array($action, ['publish', 'schedule'], true) && $post && $post->editorial_status === Post::EDITORIAL_PENDING_REVIEW) {
            throw ValidationException::withMessages(['save_action' => 'ابتدا مطلب را از صف بازبینی تایید کنید.']);
        }

        if (in_array($action, ['publish', 'schedule'], true) && $post && $post->editorial_status === Post::EDITORIAL_REJECTED) {
            throw ValidationException::withMessages(['save_action' => 'مطلب رد شده را نمی‌توان بدون ویرایش مجدد منتشر کرد.']);
        }

        if ($action === 'publish') {
            $data['is_published'] = true;
            $data['editorial_status'] = Post::EDITORIAL_PUBLISHED;
            $data['published_at'] = $publishedAt ?: ($post?->published_at ?: now());

            return;
        }

        if ($action === 'schedule') {
            $data['is_published'] = true;
            $data['editorial_status'] = Post::EDITORIAL_PUBLISHED;
            $data['published_at'] = $publishedAt;

            return;
        }

        if ($action === 'review') {
            $data['is_published'] = false;
            $data['published_at'] = null;
            $data['editorial_status'] = Post::EDITORIAL_PENDING_REVIEW;

            return;
        }

        if (! $canModerate) {
            $data['is_published'] = false;
            $data['published_at'] = null;
            if ($action === 'review') {
                $data['editorial_status'] = Post::EDITORIAL_PENDING_REVIEW;

                return;
            }
            if ($post && $post->editorial_status === Post::EDITORIAL_PENDING_REVIEW) {
                $data['editorial_status'] = Post::EDITORIAL_DRAFT;

                return;
            }
            if ($post && $post->editorial_status === Post::EDITORIAL_APPROVED) {
                $data['editorial_status'] = Post::EDITORIAL_APPROVED;

                return;
            }
            if ($post && $post->editorial_status === Post::EDITORIAL_REJECTED) {
                $data['editorial_status'] = Post::EDITORIAL_DRAFT;

                return;
            }
            $data['editorial_status'] = Post::EDITORIAL_DRAFT;

            return;
        }

        $data['is_published'] = $request->boolean('is_published', (bool) ($post?->is_published ?? false));
        if (! $data['is_published']) {
            $data['published_at'] = null;
            $data['editorial_status'] = Post::EDITORIAL_DRAFT;

            return;
        }

        $data['published_at'] = $publishedAt ?: ($post?->published_at ?: now());
        $data['editorial_status'] = Post::EDITORIAL_PUBLISHED;
    }

    private function storeSuccessMessage(string $action): string
    {
        return match ($action) {
            'publish' => 'مطلب منتشر شد.',
            'schedule' => 'مطلب با زمان‌بندی ذخیره شد.',
            'review' => 'مطلب برای بازبینی ارسال شد.',
            default => 'مطلب ذخیره شد.',
        };
    }

    private function updateSuccessMessage(string $action): string
    {
        return match ($action) {
            'publish' => 'مطلب با موفقیت منتشر/به‌روزرسانی شد.',
            'schedule' => 'زمان‌بندی مطلب به‌روزرسانی شد.',
            'review' => 'مطلب برای بازبینی به‌روزرسانی شد.',
            default => 'مطلب به‌روزرسانی شد.',
        };
    }
}
