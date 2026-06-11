<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralCode;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function index()
    {
        $codes = ReferralCode::with(['user', 'referrals.referredUser'])
            ->withCount(['referrals', 'referrals as rewarded_count' => fn ($q) => $q->where('status', Referral::STATUS_REWARDED)])
            ->orderByDesc('created_at')
            ->paginate(15);

        $stats = [
            'total_codes'    => ReferralCode::count(),
            'active_codes'   => ReferralCode::where('is_active', true)->count(),
            'total_referrals'=> Referral::count(),
            'rewarded'       => Referral::where('status', Referral::STATUS_REWARDED)->count(),
            'pending'        => Referral::where('status', Referral::STATUS_PENDING)->count(),
            'points_awarded' => Referral::where('status', Referral::STATUS_REWARDED)->sum('points_awarded'),
        ];

        return view('admin.referrals.index', compact('codes', 'stats'));
    }
}
