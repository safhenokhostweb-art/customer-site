<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);
        return implode('، ', $parts);
    }

    protected static function booted(): void
    {
        static::saving(function (self $address) {
            if ($address->is_default) {
                static::where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }
}
