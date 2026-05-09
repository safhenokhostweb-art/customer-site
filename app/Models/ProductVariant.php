<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'label', 'price_override', 'sale_price', 'stock', 'image',
        'sort_order', 'min_order_qty', 'max_order_qty', 'allow_backorder',
    ];

    protected function casts(): array
    {
        return [
            'price_override' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'allow_backorder' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getEffectivePriceAttribute(): float
    {
        $base = $this->product;
        $regular = $this->price_override !== null
            ? (float) $this->price_override
            : (float) $base->price;
        $sale = $this->sale_price !== null ? (float) $this->sale_price : null;
        if ($sale !== null && $sale < $regular) {
            return $sale;
        }
        return $regular;
    }

    public function getRegularPriceAttribute(): float
    {
        $base = $this->product;
        return $this->price_override !== null
            ? (float) $this->price_override
            : (float) $base->price;
    }

    public function isInStock(int $qty = 1): bool
    {
        if ($this->allow_backorder || $this->product->allow_backorder) {
            return true;
        }
        if ($this->stock === null) {
            return true;
        }
        return (int) $this->stock >= $qty;
    }
}
