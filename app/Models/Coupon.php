<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code',
        'discount_type',
        'discount_percent',
        'discount_amount',
        'min_order_amount',
        'expires_at',
        'starts_at',
        'usage_limit',
        'used_count',
        'is_active',
        'product_ids',
        'category_ids',
        'first_order_only',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'starts_at' => 'datetime',
        'is_active' => 'boolean',
        'min_order_amount' => 'integer',
        'discount_amount' => 'integer',
        'product_ids' => 'array',
        'category_ids' => 'array',
        'first_order_only' => 'boolean',
    ];

    /**
     * آیا کوپن برای مبلغ سفارش و سبد معتبر است؟
     *
     * @param array $cart سبد به صورت [ product_id => ['id' => ..., 'quantity' => ..., ...], ... ]
     */
    public function isValidForAmount(int $subtotal, array $cart = [], ?int $userId = null): bool
    {
        if (!$this->isValid()) {
            return false;
        }
        if ($this->min_order_amount !== null && $subtotal < $this->min_order_amount) {
            return false;
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->first_order_only && $userId && \App\Models\Order::where('user_id', $userId)->whereIn('status', ['paid', 'completed'])->exists()) {
            return false;
        }
        if ($this->hasProductRestriction() && !$this->cartMatchesProductRestriction($cart)) {
            return false;
        }
        if ($this->hasCategoryRestriction() && !$this->cartMatchesCategoryRestriction($cart)) {
            return false;
        }
        return true;
    }

    public function hasProductRestriction(): bool
    {
        $ids = $this->product_ids;
        return is_array($ids) && count($ids) > 0;
    }

    public function hasCategoryRestriction(): bool
    {
        $ids = $this->category_ids;
        return is_array($ids) && count($ids) > 0;
    }

    protected function cartMatchesProductRestriction(array $cart): bool
    {
        $allowed = $this->product_ids;
        if (empty($allowed)) {
            return true;
        }
        foreach (array_keys($cart) as $productId) {
            if (in_array((int) $productId, $allowed, true)) {
                return true;
            }
        }
        return false;
    }

    protected function cartMatchesCategoryRestriction(array $cart): bool
    {
        $allowedCategories = $this->category_ids;
        if (empty($allowedCategories)) {
            return true;
        }
        $productIds = array_keys($cart);
        if (empty($productIds)) {
            return false;
        }
        return \App\Models\Product::whereIn('id', $productIds)
            ->whereHas('category', fn ($q) => $q->whereIn('id', $allowedCategories))
            ->exists();
    }

    /**
     * مبلغ تخفیف بر اساس نوع (درصد یا مبلغ ثابت) و جمع سبد یا مبلغ قابل تخفیف.
     */
    public function calculateDiscount(int $subtotal, int $applicableSubtotal = null): int
    {
        $applicableSubtotal = $applicableSubtotal ?? $subtotal;
        if ($this->discount_type === self::TYPE_FIXED && $this->discount_amount !== null) {
            return min($this->discount_amount, $applicableSubtotal);
        }
        return (int) round($applicableSubtotal * (($this->discount_percent ?? 0) / 100));
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }
        return true;
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }
}
