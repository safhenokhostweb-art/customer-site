<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\SecurityPasswordRequest;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SecurityController extends Controller
{
    public function index()
    {
        $sessions = $this->getUserSessions();

        return view('account.security.index', compact('sessions'));
    }

    public function updatePassword(SecurityPasswordRequest $request)
    {
        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        UserActivityLog::log('password_changed', 'تغییر رمز عبور');

        return back()->with('success', 'رمز عبور با موفقیت تغییر کرد.');
    }

    public function destroySession(Request $request, string $sessionId)
    {
        $user = $request->user();
        if ($request->session()->getId() === $sessionId) {
            return back()->with('error', 'نمی‌توانید نشست فعلی را حذف کنید.');
        }

        $driver = config('session.driver');
        if ($driver === 'database') {
            \DB::table('sessions')->where('id', $sessionId)->where('user_id', $user->id)->delete();
        }

        return back()->with('success', 'نشست با موفقیت حذف شد.');
    }

    protected function getUserSessions(): array
    {
        $driver = config('session.driver');
        if ($driver !== 'database') {
            return [];
        }

        $sessions = \DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderByDesc('last_activity')
            ->get();

        $currentId = session()->getId();
        $result = [];
        foreach ($sessions as $s) {
            $result[] = (object) [
                'id' => $s->id,
                'ip_address' => $s->ip_address,
                'user_agent' => $s->user_agent,
                'last_activity' => $s->last_activity,
                'is_current' => $s->id === $currentId,
            ];
        }

        return $result;
    }
}
