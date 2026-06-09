<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteContentStep extends Model
{
    protected $fillable = ['content_id', 'sort_order', 'title', 'body', 'media_path', 'meta'];

    protected $casts = ['meta' => 'array'];

    public function content(): BelongsTo
    {
        return $this->belongsTo(SiteContent::class, 'content_id');
    }
}
