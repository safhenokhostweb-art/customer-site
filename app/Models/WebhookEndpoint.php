<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookEndpoint extends Model
{
    // Available event types for subscription webhooks
    public const EVENTS = [
        'subscription.created'    => 'اشتراک جدید فعال شد',
        'subscription.renewed'    => 'اشتراک تمدید شد',
        'subscription.upgraded'   => 'اشتراک ارتقا یافت',
        'subscription.downgraded' => 'اشتراک تنزل یافت',
        'subscription.cancelled'  => 'اشتراک لغو شد',
        'subscription.expired'    => 'اشتراک منقضی شد',
    ];

    protected $fillable = [
        'url',
        'description',
        'events',
        'secret',
        'is_active',
        'last_called_at',
        'last_status_code',
    ];

    protected $casts = [
        'events'         => 'array',
        'is_active'      => 'boolean',
        'last_called_at' => 'datetime',
    ];

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }

    public function subscribesTo(string $event): bool
    {
        return in_array($event, $this->events ?? [], true);
    }
}
