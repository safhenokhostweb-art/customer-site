<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportConversationParticipant extends Model
{
    protected $fillable = [
        'conversation_id',
        'participant_id',
        'participant_type',
        'role',
        'display_name',
        'joined_at',
        'left_at',
        'last_read_at',
        'unread_count',
    ];

    protected $casts = [
        'joined_at'    => 'datetime',
        'left_at'      => 'datetime',
        'last_read_at' => 'datetime',
        'unread_count' => 'integer',
    ];

    const TYPE_USER          = 'user';
    const TYPE_ADMIN         = 'admin';
    const TYPE_SUPPORT_AGENT = 'support_agent';
    const TYPE_BOT           = 'bot';

    const ROLE_OWNER       = 'owner';
    const ROLE_PARTICIPANT = 'participant';
    const ROLE_OBSERVER    = 'observer';

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'conversation_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('left_at');
    }

    public function scopeForParticipant($query, string $id, string $type)
    {
        return $query->where('participant_id', $id)->where('participant_type', $type);
    }
}
