<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequestAccountDeletionRequest;
use App\Models\AccountDeletionRequest as AccountDeletionRequestModel;
use Illuminate\Http\Request;

class ProfileDeletionController extends Controller
{
    public function index()
    {
        $pendingRequest = auth()->user()->deletionRequests()
            ->where('status', AccountDeletionRequestModel::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        return view('account.profile.deletion', compact('pendingRequest'));
    }

    public function store(RequestAccountDeletionRequest $request)
    {
        $user = $request->user();

        $existing = $user->deletionRequests()
            ->where('status', AccountDeletionRequestModel::STATUS_PENDING)
            ->first();

        if ($existing) {
            return back()->with('error', 'درخواست حذف حساب قبلاً ثبت شده است.');
        }

        $graceDays = (int) config('profile.deletion_grace_days', 14);

        AccountDeletionRequestModel::create([
            'user_id' => $user->id,
            'reason' => $request->validated('reason'),
            'status' => AccountDeletionRequestModel::STATUS_PENDING,
            'requested_at' => now(),
            'expires_at' => $graceDays > 0 ? now()->addDays($graceDays) : null,
        ]);

        return back()->with('success', 'درخواست حذف حساب ثبت شد. تا ' . $graceDays . ' روز می‌توانید آن را لغو کنید.');
    }

    public function cancel(Request $request)
    {
        $user = $request->user();

        $deletionRequest = $user->deletionRequests()
            ->where('status', AccountDeletionRequestModel::STATUS_PENDING)
            ->first();

        if (!$deletionRequest) {
            return back()->with('error', 'درخواست حذف یافت نشد.');
        }

        $deletionRequest->update(['status' => AccountDeletionRequestModel::STATUS_CANCELLED]);

        return back()->with('success', 'درخواست حذف حساب لغو شد.');
    }
}
