<?php

namespace App\Console\Commands;

use App\Helpers\NumberHelper;
use App\Models\MembershipPoint;
use App\Models\User;
use App\Services\MembershipPointsService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessBirthdayGiftsCommand extends Command
{
    protected $signature = 'membership:birthday-gifts
                            {--dry-run : فقط تعداد را نمایش بده بدون اعمال}';

    protected $description = 'ارسال هدیه امتیاز تولد به کاربرانی که امروز تولدشان است';

    private const BIRTHDAY_POINTS = 200;

    public function handle(MembershipPointsService $pointsService, SmsService $sms): int
    {
        $today   = now();
        $month   = $today->month;
        $day     = $today->day;
        $dryRun  = $this->option('dry-run');

        // کاربرانی که تولدشان امروز است
        $users = User::whereNotNull('birth_date')
            ->whereMonth('birth_date', $month)
            ->whereDay('birth_date', $day)
            ->get();

        if ($dryRun) {
            $eligible = $users->filter(fn ($u) => ! $this->alreadyReceivedBirthdayGift($u));
            $this->info("dry-run | واجد شرایط: {$eligible->count()} کاربر از {$users->count()}");
            return self::SUCCESS;
        }

        $gifted = 0;
        foreach ($users as $user) {
            if ($this->alreadyReceivedBirthdayGift($user)) {
                continue;
            }
            try {
                $this->giveBirthdayGift($user, $pointsService, $sms);
                $gifted++;
            } catch (\Throwable $e) {
                Log::error('birthday gift failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("✓ {$gifted} هدیه تولد ارسال شد.");
        return self::SUCCESS;
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function alreadyReceivedBirthdayGift(User $user): bool
    {
        return MembershipPoint::where('user_id', $user->id)
            ->where('source', MembershipPoint::SOURCE_BIRTHDAY)
            ->whereYear('created_at', now()->year)
            ->exists();
    }

    private function giveBirthdayGift(User $user, MembershipPointsService $pointsService, SmsService $sms): void
    {
        // ثبت امتیاز تولد
        MembershipPoint::create([
            'user_id'        => $user->id,
            'amount'         => self::BIRTHDAY_POINTS,
            'balance_after'  => $pointsService->getBalance($user) + self::BIRTHDAY_POINTS,
            'type'           => MembershipPoint::TYPE_EARN,
            'source'         => MembershipPoint::SOURCE_BIRTHDAY,
            'description'    => 'هدیه تولد ' . now()->year,
            'reference_id'   => null,
            'reference_type' => null,
            'expires_at'     => now()->addDays(MembershipPointsService::EXPIRY_DAYS),
        ]);

        // ارسال SMS
        if ($user->mobile) {
            $pts = NumberHelper::formatFa(self::BIRTHDAY_POINTS);
            $sms->send(
                $user->mobile,
                "سلام {$user->name} عزیز،\n" .
                "تولدت مبارک! 🎂\n" .
                "{$pts} امتیاز هدیه تولد به حسابت اضافه شد.\n" .
                "برای استفاده از امتیازات در خریدت، وارد پنل کاربری شو."
            );
        }
    }
}
