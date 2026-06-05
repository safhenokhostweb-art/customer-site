<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price', 'special_price', 'discount_code', 'discount_percent',
        'duration', 'duration_count', 'is_active', 'order',
        'trial_days', 'features', 'color', 'highlighted', 'badge_text', 'max_users', 'image',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'highlighted'      => 'boolean',
        'features'         => 'array',
        'price'            => 'decimal:0',
        'special_price'    => 'decimal:0',
        'discount_percent' => 'decimal:2',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'active');
    }

    public function isFree(): bool
    {
        return (float) $this->price === 0.0;
    }

    public function hasTrial(): bool
    {
        return $this->trial_days > 0;
    }

    public function getDurationLabel(): string
    {
        $unit = $this->duration === 'year' ? 'ساله' : 'ماهه';
        return $this->duration_count . ' ' . $unit;
    }

    public function getDurationInDays(): int
    {
        return $this->duration === 'year'
            ? $this->duration_count * 365
            : $this->duration_count * 30;
    }

    public function getFeaturesArray(): array
    {
        if (is_array($this->features)) {
            return $this->features;
        }
        return [];
    }

    public function getEffectivePrice(): float
    {
        if ($this->special_price !== null && (float) $this->special_price > 0) {
            return (float) $this->special_price;
        }
        return (float) $this->price;
    }

    public function hasDiscount(): bool
    {
        return ($this->special_price !== null && (float) $this->special_price < (float) $this->price)
            || ($this->discount_percent !== null && (float) $this->discount_percent > 0);
    }

    public function getDiscountedPrice(string $code = ''): float
    {
        $base = $this->getEffectivePrice();
        if ($code && $this->discount_code && strtolower($this->discount_code) === strtolower($code)
            && $this->discount_percent > 0) {
            return round($base * (1 - ($this->discount_percent / 100)));
        }
        return $base;
    }

    public function getFormattedPrice(): string
    {
        $effective = $this->getEffectivePrice();
        if ($effective === 0.0) {
            return 'رایگان';
        }
        return number_format($effective) . ' تومان';
    }
}
