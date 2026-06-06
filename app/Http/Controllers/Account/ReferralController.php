<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(private ReferralService $referralService) {}

    public function index(Request $request)
    {
        $user  = $request->user();
        $code  = $this->referralService->getOrCreateCode($user);
        $stats = $this->referralService->getStats($user);

        return view('account.referrals.index', compact('code', 'stats'));
    }
}
