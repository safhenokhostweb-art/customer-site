<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportConversation extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'status',
        'type',
        'subject',
        'priority',
        'channel',
        'assigned_agent_id',
        'created_by_user_id',
        'created_by_type',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'last_message_at',
        'metadata',
        'tags',
    ];

    protected $casts = [
        'metadata'           => 'array',
        'tags'               => 'array',
        'first_response_at'  => 'datetime',
        'resolved_at'        => 'datetime',
        'closed_at'          => 'datetime',
        'last_message_at'    => 'datetime',
    ];

    // Status constants — no cross-platform values
    const STATUS_OPEN     = 'open';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_CLOSED   = 'closed';
    const STATUS_ARCHIVED = 'archived';

    const PRIORITY_LOW    = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH   = 'high';
    const PRIORITY_URGENT = 'urgent';

    public function participants(): HasMany
    {
        return $this->hasMany(SupportConversationParticipant::class, 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'conversation_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportMessageAttachment::class, 'conversation_id');
    }

    public function presence(): HasMany
    {
        return $this->hasMany(SupportPresence::class, 'conversation_id');
    }

    public function readReceipts(): HasMany
    {
        return $this->hasMany(SupportReadReceipt::class, 'conversation_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', self::STATUS_ASSIGNED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_ASSIGNED]);
    }

    public function scopeForAgent($query, int $agentId)
    {
        return $query->where('assigned_agent_id', $agentId);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
