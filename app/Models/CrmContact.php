<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmContact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'mobile', 'email', 'company', 'position',
        'source', 'status', 'city', 'notes', 'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'contact_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'contact_id')->latest();
    }

    public function activeDeals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'contact_id')
            ->whereNotIn('stage', ['won', 'lost']);
    }

    public static function statusLabel(string $status): string
    {
        return match($status) {
            'lead'     => 'سرنخ',
            'prospect' => 'بالقوه',
            'customer' => 'مشتری',
            'churned'  => 'از دست رفته',
            default    => $status,
        };
    }

    public static function statusColor(string $status): string
    {
        return match($status) {
            'lead'     => 'blue',
            'prospect' => 'amber',
            'customer' => 'emerald',
            'churned'  => 'red',
            default    => 'slate',
        };
    }
}
