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
        if (is_array($v2) && !empty($v2['sections'] ?? [])) {
            return $v2;
        }
        $v1 = $this->builder_content;
        if (is_array($v1) && !empty($v1['sections'] ?? [])) {
            return $v1;
        }
        return ['sections' => []];
    }

    public function usesBuilder(): bool
    {
        $content = $this->getBuilderContentForRender();
        return $this->builder_type === self::BUILDER_TYPE_BUILDER
            && !empty($content['sections'] ?? []);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeMenu($query)
    {
        return $query->where('show_in_menu', true)->orderBy('order');
    }
}
