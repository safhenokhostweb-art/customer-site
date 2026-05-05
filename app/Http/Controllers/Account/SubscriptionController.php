<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        if (!SiteHelper::moduleEnabled('subscription')) {
            abort(404);
        }

        $user = auth()->user();
        $subscriptions = Subscription::with('plan')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('account.subscriptions.index', compact('subscriptions'));
    }

    public function cancel(Subscription $subscription)
    {
        if (!SiteHelper::moduleEnabled('subscription')) {
            abort(404);
        }

        $user = auth()->user();
        if ($subscription->user_id !== $user->id) {
            abort(404);
        }

        if (!$subscription->isActive()) {
            return back()->with('error', 'این اشتراک فعال نیست.');
        }

        $subscription->update(['status' => 'cancelled']);

        return back()->with('success', 'اشتراک با موفقیت لغو شد.');
    }
}
