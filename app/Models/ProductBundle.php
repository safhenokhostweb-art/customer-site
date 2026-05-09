<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBundle extends Model
{
    protected $fillable = ['parent_product_id', 'child_product_id', 'quantity'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'child_product_id');
    }
}
