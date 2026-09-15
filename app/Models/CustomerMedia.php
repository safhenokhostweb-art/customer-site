<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerMedia extends Model
{
    protected $table = 'customer_media';

    protected $fillable = [
        'site_id', 'uuid', 'disk', 'storage_key', 'original_filename',
        'extension', 'category', 'mime_type', 'size_bytes', 'title',
        'alt_text', 'caption', 'description', 'width', 'height', 'duration_seconds', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer', 'width' => 'integer', 'height' => 'integer',
            'duration_seconds' => 'integer', 'metadata' => 'array',
            'deleted_at' => 'datetime', 'purged_at' => 'datetime',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CustomerMediaUsage::class);
    }

    public function scopeForSite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeAvailable($query)
    {
        return $query->whereNull('deleted_at');
    }
}
