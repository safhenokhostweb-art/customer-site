<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id', 'parent_id', 'title', 'type', 'reference_id', 'url',
        'icon', 'order', 'open_in_new_tab', 'css_class',
    ];

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
        ];
    }

    public const TYPE_INTERNAL_PAGE = 'internal_page';
    public const TYPE_BLOG = 'blog';
    public const TYPE_SHOP = 'shop';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_CUSTOM_URL = 'custom_url';

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    /**
     * Resolve the URL for this item based on type and reference.
     */
    public function resolveUrl(): string
    {
        if ($this->type === self::TYPE_CUSTOM_URL && $this->url) {
            return $this->url;
        }
        if ($this->type === self::TYPE_INTERNAL_PAGE && $this->reference_id) {
            $page = Page::find($this->reference_id);
            return $page ? route('page.show', $page->slug) : '#';
        }
        if ($this->type === self::TYPE_BLOG) {
            return route('blog.index');
        }
        if ($this->type === self::TYPE_SHOP) {
            return route('shop.index');
        }
        if ($this->type === self::TYPE_CATEGORY && $this->reference_id) {
            $cat = \App\Models\ProductCategory::find($this->reference_id);
            return $cat ? route('shop.index', ['category' => $cat->slug ?? $cat->id]) : route('shop.index');
        }
        return $this->url ?? '#';
    }
}
