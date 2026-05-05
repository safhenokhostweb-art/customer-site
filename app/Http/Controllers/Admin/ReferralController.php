<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralCode;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function index()
    {
        $codes = ReferralCode::with('user', 'referrals')->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.referrals.index', compact('codes'));
    }
}
