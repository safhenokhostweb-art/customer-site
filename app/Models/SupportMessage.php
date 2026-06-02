<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportMessage extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'message_type',
        'content',
        'reply_to_message_id',
        'is_internal_note',
        'is_deleted',
        'metadata',
        'edited_at',
    ];

    protected $casts = [
        'metadata'         => 'array',
        'is_internal_note' => 'boolean',
        'is_deleted'       => 'boolean',
        'edited_at'        => 'datetime',
    ];

    const TYPE_TEXT    = 'text';
    const TYPE_IMAGE   = 'image';
    const TYPE_FILE    = 'file';
    const TYPE_VOICE   = 'voice';
    const TYPE_SYSTEM  = 'system';
    const TYPE_NOTE    = 'note';

    const SENDER_USER          = 'user';
    const SENDER_ADMIN         = 'admin';
    const SENDER_SUPPORT_AGENT = 'support_agent';
    const SENDER_BOT           = 'bot';
    const SENDER_SYSTEM        = 'system';

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'conversation_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportMessageAttachment::class, 'message_id');
    }

    public function readReceipts(): HasMany
    {
        return $this->hasMany(SupportReadReceipt::class, 'message_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class, 'reply_to_message_id');
    }

    public function scopeVisible($query)
    {
        return $query->where('is_deleted', false)->whereNull('deleted_at');
    }

    public function scopePublic($query)
    {
        return $query->where('is_internal_note', false)->visible();
    }
}
