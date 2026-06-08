<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEvent extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'webhook_endpoint_id',
        'event_type',
        'payload',
        'status',
        'attempts',
        'last_attempted_at',
        'response_code',
        'response_body',
    ];

    protected $casts = [
        'payload'           => 'array',
        'last_attempted_at' => 'datetime',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}
