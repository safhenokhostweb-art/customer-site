<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationBatch extends Model
{
    protected $table = 'notification_batches';

    protected $fillable = [
        'title',
        'body',
        'type',
        'link',
        'data',
        'sent_at',
        'recipient_count',
        'created_by',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
    ];

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'notification_batch_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
