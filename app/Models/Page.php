<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    public const BUILDER_TYPE_LEGACY = 'legacy';
    public const BUILDER_TYPE_BUILDER = 'builder';

    protected $fillable = [
        'title', 'slug', 'body', 'builder_content', 'builder_content_v2', 'builder_type',
        'builder_header_template_override', 'builder_footer_template_override',
        'builder_disable_header', 'builder_disable_footer',
        'meta_title', 'meta_description', 'order', 'is_published', 'show_in_menu',
        'is_protected', 'theme_id', 'theme_page_key',
    ];

    protected function casts(): array
    {
        return [
            'is_published'         => 'boolean',
            'show_in_menu'         => 'boolean',
            'is_protected'         => 'boolean',
            'builder_disable_header' => 'boolean',
            'builder_disable_footer' => 'boolean',
            'builder_content'      => 'array',
            'builder_content_v2'   => 'array',
        ];
    }

    public function revisions()
    {
        return $this->hasMany(PageRevision::class)->orderByDesc('created_at');
    }

    public function recentRevisions(int $days = 7)
    {
        return $this->revisions()->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Content for visual builder: prefer v2 if present, else fallback to v1.
     */
    public function getBuilderContentForRender(): array
    {
        $v2 = $this->builder_content_v2;
        if (is_array($v2)) {
            if (!empty($v2['blocks'] ?? [])) {
                return ['sections' => $v2['blocks'], 'blocks' => $v2['blocks']];
            }
            if (!empty($v2['sections'] ?? [])) {
                return ['sections' => $v2['sections'], 'blocks' => $v2['sections']];
            }
        }
        $v1 = $this->builder_content;
        if (is_array($v1)) {
            if (!empty($v1['blocks'] ?? [])) {
                return ['sections' => $v1['blocks'], 'blocks' => $v1['blocks']];
            }
            if (!empty($v1['sections'] ?? [])) {
                return ['sections' => $v1['sections'], 'blocks' => $v1['sections']];
            }
        }
        return ['sections' => [], 'blocks' => []];
    }

    public function usesBuilder(): bool
    {
        $content = $this->getBuilderContentForRender();
        return $this->builder_type === self::BUILDER_TYPE_BUILDER
            && (!empty($content['sections'] ?? []) || !empty($content['blocks'] ?? []));
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeMenu($query)
    {
        return $query->where('show_in_menu', true)->orderBy('order');
    }

    /**
     * TASK 3/3 Phase 6.5B (BLD-22) — MenuService::buildMenuTree() caches
     * the fully RESOLVED tree, including each internal_page MenuItem's
     * resolveUrl() output (i.e. this Page's slug at cache-build time), for
     * up to an hour. Without this hook, renaming a Page's slug left every
     * Menu referencing it silently serving a stale, wrong (eventually
     * 404ing) URL until that cache happened to expire — a real regression
     * a customer would experience as "I renamed a page and now my own menu
     * link is broken." A model-level hook (not a per-controller fix) is
     * the canonical place: every current and future write path that
     * changes a Page's slug (admin Page edit, Builder's own page-settings
     * API, bulk tools, …) is covered automatically, with no risk of a new
     * caller forgetting to invalidate the cache by hand.
     */
    protected static function booted(): void
    {
        static::updated(function (self $page) {
            if ($page->wasChanged('slug')) {
                app(\App\Services\MenuService::class)->clearMenuCache();
            }
        });
    }
}
