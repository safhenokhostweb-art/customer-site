<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivity extends Model
{
    protected $fillable = [
        'contact_id', 'deal_id', 'admin_id', 'type', 'subject', 'body',
        'outcome', 'scheduled_at', 'completed_at',
    ];

    protected $casts = [
        'scheduled_at'  => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(CrmDeal::class, 'deal_id');
    }

    public static function typeLabel(string $type): string
    {
        return match($type) {
            'call'    => 'تماس تلفنی',
            'email'   => 'ایمیل',
            'meeting' => 'جلسه',
            'note'    => 'یادداشت',
            'task'    => 'وظیفه',
            default   => $type,
        };
    }

    public static function typeIcon(string $type): string
    {
        return match($type) {
            'call'    => 'phone',
            'email'   => 'mail',
            'meeting' => 'users',
            'note'    => 'document',
            'task'    => 'check-circle',
            default   => 'document',
        };
    }
}
