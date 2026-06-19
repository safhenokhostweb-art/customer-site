<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SiteThemeTemplate extends Model
{
    protected $fillable = [
        'uuid', 'type', 'category', 'name', 'description', 'tags', 'author',
        'version', 'license_type', 'blocks', 'global_styles', 'demo_data',
        'thumbnail_url', 'is_active', 'usage_count',
    ];

    protected $casts = [
        'tags'         => 'array',
        'blocks'       => 'array',
        'global_styles'=> 'array',
        'demo_data'    => 'array',
        'is_active'    => 'boolean',
    ];

    const TYPES = [
        'section'      => 'بخش',
        'page'         => 'صفحه',
        'header'       => 'هدر',
        'footer'       => 'فوتر',
        'mega_menu'    => 'مگامنو',
        'popup'        => 'پاپ‌آپ',
        'landing_page' => 'لندینگ پیج',
    ];

    const CATEGORIES = [
        'hero'         => 'هیرو',
        'features'     => 'ویژگی‌ها',
        'pricing'      => 'قیمت‌گذاری',
        'testimonials' => 'نظرات',
        'faq'          => 'سوالات متداول',
        'contact'      => 'تماس',
        'team'         => 'تیم',
        'gallery'      => 'گالری',
        'blog'         => 'وبلاگ',
        'product'      => 'محصول',
        'checkout'     => 'پرداخت',
        'corporate'    => 'شرکتی',
        'personal'     => 'شخصی',
        'store'        => 'فروشگاه',
        'education'    => 'آموزش',
    ];

    protected static function booted(): void
    {
        static::creating(fn($m) => $m->uuid ??= (string) Str::uuid());
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeByType($q, string $t) { return $q->where('type', $t); }
    public function scopeByCategory($q, string $c) { return $q->where('category', $c); }

    public function typeLabel(): string { return self::TYPES[$this->type] ?? $this->type; }
    public function categoryLabel(): string { return self::CATEGORIES[$this->category] ?? $this->category; }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
