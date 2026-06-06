<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index(Request $request)
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('order')
            ->orderBy('price')
            ->get();

        $activePlanId = null;
        if ($request->user()) {
            $activeSub    = $request->user()->subscriptions()
                ->whereIn('status', ['active', 'trial'])
                ->with('plan')
                ->latest('starts_at')
                ->first();
            $activePlanId = $activeSub?->subscription_plan_id;
        }

        return view('front.pricing.index', compact('plans', 'activePlanId'));
    }
}
