<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AdminNotification extends Model
{
    protected $fillable = ['type', 'title', 'body', 'link', 'is_read', 'read_at'];

    protected $casts = [
        'is_read'  => 'boolean',
        'read_at'  => 'datetime',
    ];

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function markRead(): void
    {
        if (! $this->is_read) {
            $this->update(['is_read' => true, 'read_at' => now()]);
        }
    }
}
