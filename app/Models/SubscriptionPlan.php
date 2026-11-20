<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price', 'special_price', 'discount_code',
        'duration', 'duration_count', 'is_active', 'order',
        'trial_days', 'features', 'benefits', 'color', 'highlighted', 'badge_text',
        'max_users', 'image',
        'product_discount_percent', 'apply_to_all_products', 'coupon_override',
        'free_shipping', 'vip_support', 'early_access', 'loyalty_multiplier',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'highlighted'             => 'boolean',
        'apply_to_all_products'   => 'boolean',
        'coupon_override'         => 'boolean',
        'free_shipping'           => 'boolean',
        'vip_support'             => 'boolean',
        'early_access'            => 'boolean',
        'features'                => 'array',
        'benefits'                => 'array',
        'price'               => 'decimal:0',
        'special_price'       => 'decimal:0',
        'loyalty_multiplier'  => 'float',
    ];

    /** Key prefix of plan images stored on the media disk (Phase 4). */
    public const IMAGE_PREFIX = 'plans/';

    /**
     * Public URL of the plan image. Images stored since Phase 4 live on the
     * media disk (MEDIA_DISK: local uploads or object storage) under
     * "plans/..."; older values are paths on the local "public" disk.
     */
    public function getImageUrlAttribute(): ?string
    {
        $image = (string) ($this->image ?? '');
        if ($image === '') {
            return null;
        }

        if (str_starts_with($image, self::IMAGE_PREFIX)) {
            return app(\App\Services\MediaStorage::class)->url($image, 'uploads') ?: null;
        }

        return asset('storage/' . $image);
    }

    /** Delete the stored image wherever it lives (best effort). */
    public function deleteStoredImage(): void
    {
        $image = (string) ($this->image ?? '');
        if ($image === '') {
            return;
        }

        str_starts_with($image, self::IMAGE_PREFIX)
            ? app(\App\Services\MediaStorage::class)->delete($image, 'uploads')
            : \Illuminate\Support\Facades\Storage::disk('public')->delete($image);
    }

    // ─── Relations ────────────────────────────────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'active');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'subscription_plan_products')
            ->withPivot('discount_percent', 'override_coupon_exclusion')
            ->withTimestamps();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function isFree(): bool
    {
        return (float) $this->price === 0.0;
    }

    public function hasTrial(): bool
    {
        return $this->trial_days > 0;
    }

    public function hasSpecialPrice(): bool
    {
        return $this->special_price !== null
            && (float) $this->special_price > 0
            && (float) $this->special_price < (float) $this->price;
    }

    public function getDurationLabel(): string
    {
        return match ($this->duration) {
            'day'   => \App\Helpers\NumberHelper::formatFa($this->duration_count) . ' روزه',
            'year'  => \App\Helpers\NumberHelper::formatFa($this->duration_count) . ' ساله',
            default => \App\Helpers\NumberHelper::formatFa($this->duration_count) . ' ماهه',
        };
    }

    public function getDurationInDays(): int
    {
        return match ($this->duration) {
            'day'   => $this->duration_count,
            'year'  => $this->duration_count * 365,
            default => $this->duration_count * 30,
        };
    }

    public function getFeaturesArray(): array
    {
        return is_array($this->features) ? $this->features : [];
    }

    public function getBenefitsArray(): array
    {
        return is_array($this->benefits) ? $this->benefits : [];
    }

    /** قیمت مؤثر: اگر قیمت ویژه دارد آن را برمی‌گرداند */
    public function getEffectivePrice(): float
    {
        if ($this->hasSpecialPrice()) {
            return (float) $this->special_price;
        }
        return (float) $this->price;
    }

    public function getFormattedPrice(): string
    {
        $effective = $this->getEffectivePrice();
        if ($effective === 0.0) {
            return 'رایگان';
        }
        return \App\Helpers\NumberHelper::formatFa($effective) . ' تومان';
    }

    /** برچسب‌های مزایا برای نمایش در کارت */
    public function getBenefitBadges(): array
    {
        $badges = [];

        if ($this->product_discount_percent > 0) {
            $badges[] = ['icon' => 'tag',      'label' => \App\Helpers\NumberHelper::formatFa($this->product_discount_percent) . '٪ تخفیف روی محصولات'];
        }
        if ($this->free_shipping) {
            $badges[] = ['icon' => 'truck',    'label' => 'ارسال رایگان'];
        }
        if ($this->vip_support) {
            $badges[] = ['icon' => 'headset',  'label' => 'پشتیبانی VIP اولویت‌دار'];
        }
        if ($this->early_access) {
            $badges[] = ['icon' => 'star',     'label' => 'دسترسی زودهنگام به محصولات'];
        }
        if ($this->loyalty_multiplier > 1) {
            $badges[] = ['icon' => 'gift',     'label' => 'ضریب امتیاز ×' . \App\Helpers\NumberHelper::formatFa($this->loyalty_multiplier)];
        }

        return $badges;
    }
}
