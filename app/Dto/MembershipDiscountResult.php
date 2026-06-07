<?php

namespace App\Dto;

use App\Models\SubscriptionPlan;

final class MembershipDiscountResult
{
    public function __construct(
        public readonly int            $discountAmount,
        public readonly float          $discountPercent,
        public readonly bool           $freeShipping,
        public readonly bool           $vipSupport,
        public readonly bool           $earlyAccess,
        public readonly bool           $appliedToAll,
        public readonly array          $appliedProductIds,
        public readonly string         $planName,
        public readonly ?SubscriptionPlan $plan,
    ) {}

    public static function empty(): self
    {
        return new self(
            discountAmount:    0,
            discountPercent:   0.0,
            freeShipping:      false,
            vipSupport:        false,
            earlyAccess:       false,
            appliedToAll:      false,
            appliedProductIds: [],
            planName:          '',
            plan:              null,
        );
    }

    public function hasDiscount(): bool
    {
        return $this->discountAmount > 0;
    }

    public function hasBenefit(): bool
    {
        return $this->discountAmount > 0
            || $this->freeShipping
            || $this->vipSupport
            || $this->earlyAccess;
    }
}
