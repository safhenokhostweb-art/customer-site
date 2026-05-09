<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'user_id', 'changes'];

    protected function casts(): array
    {
        return ['changes' => 'array'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
