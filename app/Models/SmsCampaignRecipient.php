<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsCampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id', 'mobile', 'name', 'status', 'sent_at', 'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SmsCampaign::class, 'campaign_id');
    }
}
