<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportReadReceipt extends Model
{
    protected $fillable = [
        'message_id',
        'conversation_id',
        'reader_id',
        'reader_type',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class, 'message_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'conversation_id');
    }

    public function scopeForReader($query, string $id, string $type)
    {
        return $query->where('reader_id', $id)->where('reader_type', $type);
    }
}
