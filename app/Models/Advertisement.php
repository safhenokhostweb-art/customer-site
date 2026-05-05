<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Advertisement extends Model
{
    protected $fillable = [
        'type',
        'title',
        'content',
        'image_path',
        'link',
        'position',
        'order',
        'starts_at',
        'expires_at',
        'is_active',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'options' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    public function scopePosition(Builder $query, string $position): Builder
    {
        return $query->where('position', $position);
    }

    public static function typeLabels(): array
    {
        return [
            'banner' => 'بنر تصویری',
            'scrolling_text' => 'نوشتار رونده',
            'typewriter' => 'نوشتار تایپی',
            'marquee' => 'مارکی',
            'blink' => 'چشمک‌زن',
            'fade' => 'محو شونده',
        ];
    }

    public static function positionLabels(): array
    {
        return [
            'header' => 'بالای صفحه',
            'footer' => 'پایین صفحه',
            'sidebar' => 'نوار کناری',
            'content_top' => 'بالای محتوا',
            'content_bottom' => 'پایین محتوا',
        ];
    }
}
