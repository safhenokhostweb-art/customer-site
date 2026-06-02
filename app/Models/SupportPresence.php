<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportPresence extends Model
{
    protected $fillable = [
        'conversation_id',
        'participant_id',
        'participant_type',
        'status',
        'is_typing',
        'typing_started_at',
        'last_seen_at',
        'socket_id',
    ];

    protected $casts = [
        'is_typing'         => 'boolean',
        'typing_started_at' => 'datetime',
        'last_seen_at'      => 'datetime',
    ];

    const STATUS_ONLINE  = 'online';
    const STATUS_AWAY    = 'away';
    const STATUS_OFFLINE = 'offline';

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'conversation_id');
    }

    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    public function scopeTyping($query)
    {
        return $query->where('is_typing', true);
    }

    public function clearTyping(): void
    {
        $this->update(['is_typing' => false, 'typing_started_at' => null]);
    }
}
