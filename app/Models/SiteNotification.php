<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'title', 'body', 'type', 'link', 'link_text',
        'position', 'image_position', 'image_path',
        'show_shaking_logo', 'starts_at', 'expires_at', 'is_active', 'order', 'audience',
    ];

    protected $casts = [
        'show_shaking_logo' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }
}
