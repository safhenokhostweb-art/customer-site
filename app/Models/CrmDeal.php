<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmDeal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contact_id', 'title', 'stage', 'value', 'probability',
        'expected_close_date', 'notes', 'closed_at',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
        'closed_at'           => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'deal_id')->latest();
    }

    public static function stages(): array
    {
        return [
            'lead'        => 'سرنخ',
            'contacted'   => 'تماس گرفته شد',
            'proposal'    => 'پیشنهاد ارسال شد',
            'negotiation' => 'مذاکره',
            'won'         => 'برنده شد',
            'lost'        => 'از دست رفت',
        ];
    }

    public static function stageColor(string $stage): string
    {
        return match($stage) {
            'lead'        => 'slate',
            'contacted'   => 'blue',
            'proposal'    => 'violet',
            'negotiation' => 'amber',
            'won'         => 'emerald',
            'lost'        => 'red',
            default       => 'slate',
        };
    }

    public function isActive(): bool
    {
        return !in_array($this->stage, ['won', 'lost']);
    }
}
