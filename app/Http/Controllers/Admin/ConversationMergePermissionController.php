<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PlatformApiService;
use Illuminate\Http\Request;

class ConversationMergePermissionController extends Controller
{
    public function __construct(private PlatformApiService $platform) {}

    public function index()
    {
        abort_unless(auth()->user()?->isTenantAdmin(), 403);

        $users   = User::where('is_admin', false)->orderBy('name')->get(['id', 'name', 'mobile']);
        $granted = [];

        if ($this->platform->isConfigured()) {
            $rawGrants = $this->platform->listMergePermissions();
            $granted   = collect($rawGrants)->pluck('user_id')->flip()->all();
        }

        $notConfigured = ! $this->platform->isConfigured();

        return view('admin.conversation-merge.permissions', compact('users', 'granted', 'notConfigured'));
    }

    public function grant(Request $request)
    {
        abort_unless(auth()->user()?->isTenantAdmin(), 403);
        $request->validate(['user_id' => 'required|exists:users,id']);

        if (! $this->platform->isConfigured()) {
            return back()->with('error', 'اتصال به پلتفرم تنظیم نشده است.');
        }

        $ok = $this->platform->grantMergePermission((string) $request->user_id);

        return back()->with($ok ? 'success' : 'error',
            $ok ? 'دسترسی ادغام اعطا شد.' : 'خطا در اعطای دسترسی. لطفاً مجدداً تلاش کنید.'
        );
    }

    public function revoke(Request $request, int $userId)
    {
        abort_unless(auth()->user()?->isTenantAdmin(), 403);

        if (! $this->platform->isConfigured()) {
            return back()->with('error', 'اتصال به پلتفرم تنظیم نشده است.');
        }

        $ok = $this->platform->revokeMergePermission((string) $userId);

        return back()->with($ok ? 'success' : 'error',
            $ok ? 'دسترسی ادغام لغو شد.' : 'خطا در لغو دسترسی. لطفاً مجدداً تلاش کنید.'
        );
    }
}
