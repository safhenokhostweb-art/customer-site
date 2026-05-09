<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopCartEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'event_type', 'session_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
