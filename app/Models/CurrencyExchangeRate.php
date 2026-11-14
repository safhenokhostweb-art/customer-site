<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One foreign currency's rate in Toman per unit. `rate_toman` 0 means the
 * rate has never been set; consumers must treat that as "price
 * unavailable", never as free.
 */
class CurrencyExchangeRate extends Model
{
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_ONLINE = 'online';

    protected $fillable = ['code', 'name', 'rate_toman', 'source', 'is_active', 'fetched_at'];

    protected $casts = [
        'rate_toman' => 'float',
        'is_active' => 'boolean',
        'fetched_at' => 'datetime',
    ];

    public function isSet(): bool
    {
        return $this->rate_toman > 0;
    }
}
