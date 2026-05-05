<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\UserActivityLog;

class ProfileActivityController extends Controller
{
    public function index()
    {
        $activities = UserActivityLog::where('user_id', auth()->id())
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('account.profile.activity', compact('activities'));
    }
}
