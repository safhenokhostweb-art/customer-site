<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\AccessGate;
use App\Models\AccessVerification;
use Illuminate\Http\Request;

class AccessGateController extends Controller
{
    public function show(string $slug)
    {
        $gate = AccessGate::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return view('front.access-gate.verify', compact('gate'));
    }

    public function verify(Request $request, string $slug)
    {
        $gate = AccessGate::with('accessList')->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $list = $gate->accessList;

        $rules = [];
        if (in_array($list->match_type, ['national_id', 'both'])) {
            $rules['national_id'] = 'required|string|max:20';
        }
        if (in_array($list->match_type, ['last_name', 'both'])) {
            $rules['last_name'] = 'required|string|max:100';
        }
        $request->validate($rules);

        $nationalId = $request->input('national_id');
        $lastName   = $request->input('last_name');

        $entry = $list->verify($nationalId, $lastName);
        $success = $entry !== null;

        AccessVerification::create([
            'access_gate_id'        => $gate->id,
            'access_list_entry_id'  => $entry?->id,
            'ip_address'            => $request->ip(),
            'user_agent'            => $request->userAgent(),
            'success'               => $success,
            'input_national_id'     => $nationalId,
            'input_last_name'       => $lastName,
        ]);

        if (!$success) {
            return back()->withErrors(['_' => 'اطلاعات وارد‌شده در سیستم یافت نشد. لطفاً مجدداً بررسی کنید.']);
        }

        $request->session()->put($gate->sessionKey(), [
            'verified_at' => now()->timestamp,
            'expires_at'  => now()->addMinutes($gate->session_duration)->timestamp,
        ]);

        return redirect()->to($gate->redirect_url)
            ->with('access_granted', true);
    }
}
