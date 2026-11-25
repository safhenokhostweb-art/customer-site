<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteTheme extends Model
{
    protected $fillable = [
        'theme_slug',
        'theme_name',
        'source_theme_id',
        'source_theme_version',
        'source_release_id',
        'is_active',
        'active_revision_id',
        'installed_at',
        'activated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'source_release_id' => 'integer',
        'active_revision_id' => 'integer',
        'installed_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function activeRevision(): BelongsTo
    {
        return $this->belongsTo(ThemeInstanceRevision::class, 'active_revision_id');
    }
}
