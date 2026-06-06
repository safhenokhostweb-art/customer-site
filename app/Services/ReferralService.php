<?php

namespace App\Services;

use App\Models\MembershipPoint;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReferralService
{
    // امتیاز هر طرف دعوت‌کننده و دعوت‌شده
    const POINTS_REFERRER = 300;
    const POINTS_REFERRED = 100;

    public function getOrCreateCode(User $user): ReferralCode
    {
        return ReferralCode::firstOrCreate(
            ['user_id' => $user->id],
            [
                'code'      => $this->generateUniqueCode(),
                'is_active' => true,
            ]
        );
    }

    /**
     * ثبت یک دعوت‌نامه هنگام ثبت‌نام کاربر جدید.
     * کد referral از session/request می‌آید.
     */
    public function registerReferral(User $newUser, string $code): ?Referral
    {
        $referralCode = ReferralCode::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $referralCode) {
            return null;
        }

        // جلوگیری از خود-ارجاعی
        if ($referralCode->user_id === $newUser->id) {
            return null;
        }

        // جلوگیری از ثبت تکراری
        if (Referral::where('referred_user_id', $newUser->id)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($referralCode, $newUser) {
            $referral = Referral::create([
                'referral_code_id' => $referralCode->id,
                'referred_user_id' => $newUser->id,
                'status'           => Referral::STATUS_PENDING,
            ]);

            $referralCode->increment('uses');

            return $referral;
        });
    }

    /**
     * پاداش دادن به هر دو طرف پس از اولین اشتراک دعوت‌شده.
     * idempotent — اگر قبلاً reward شده باشد، skip می‌کند.
     */
    public function rewardReferral(User $referredUser): void
    {
        $referral = Referral::where('referred_user_id', $referredUser->id)
            ->where('status', Referral::STATUS_PENDING)
            ->with('referralCode.user')
            ->first();

        if (! $referral) {
            return;
        }

        $referrer = $referral->referralCode?->user;

        if (! $referrer) {
            return;
        }

        DB::transaction(function () use ($referral, $referrer, $referredUser) {
            $pointsSvc = app(MembershipPointsService::class);

            // پاداش دعوت‌کننده
            $pointsSvc->earnFromReferral($referrer, self::POINTS_REFERRER, $referredUser);

            // پاداش دعوت‌شده
            $pointsSvc->earnFromReferral($referredUser, self::POINTS_REFERRED, $referrer);

            $referral->update([
                'status'        => Referral::STATUS_REWARDED,
                'rewarded_at'   => now(),
                'points_awarded' => self::POINTS_REFERRER + self::POINTS_REFERRED,
            ]);
        });
    }

    public function getStats(User $user): array
    {
        $code = ReferralCode::where('user_id', $user->id)->with('referrals')->first();

        if (! $code) {
            return [
                'total'    => 0,
                'rewarded' => 0,
                'pending'  => 0,
                'link'     => $this->buildLink($this->getOrCreateCode($user)->code),
            ];
        }

        $referrals = $code->referrals;

        return [
            'total'    => $referrals->count(),
            'rewarded' => $referrals->where('status', Referral::STATUS_REWARDED)->count(),
            'pending'  => $referrals->where('status', Referral::STATUS_PENDING)->count(),
            'link'     => $this->buildLink($code->code),
            'code'     => $code->code,
        ];
    }

    public function buildLink(string $code): string
    {
        return url('/register?ref=' . $code);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (ReferralCode::where('code', $code)->exists());

        return $code;
    }
}
