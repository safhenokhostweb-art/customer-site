<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Jalali;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SaaSPlatform\Modules\Tickets\Models\Ticket;

class UserPanelController extends Controller
{
    public function __construct(protected SmsService $sms) {}

    public function index(Request $request)
    {
        $query = User::where('is_admin', false);

        // جستجو
        if ($q = $request->input('q')) {
            $query->where(fn ($b) =>
                $b->where('name', 'like', "%{$q}%")
                  ->orWhere('mobile', 'like', "%{$q}%")
            );
        }

        // فیلتر وضعیت چت
        $statusFilter = $request->input('status');
        if ($statusFilter === 'with_chat') {
            $ids = Ticket::withoutTenantIsolation()->whereNull('site_id')
                ->whereNotNull('customer_id')->distinct()->pluck('customer_id');
            $query->whereIn('id', $ids);
        } elseif ($statusFilter === 'active_chat') {
            $ids = Ticket::withoutTenantIsolation()->whereNull('site_id')
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
                ->whereNotNull('customer_id')->distinct()->pluck('customer_id');
            $query->whereIn('id', $ids);
        } elseif ($statusFilter === 'no_chat') {
            $ids = Ticket::withoutTenantIsolation()->whereNull('site_id')
                ->whereNotNull('customer_id')->distinct()->pluck('customer_id');
            $query->whereNotIn('id', $ids);
        }

        // فیلتر بازه تاریخ
        if ($from = $request->input('from_date')) {
            $c = Jalali::toCarbon($from);
            $query->whereDate('created_at', '>=', $c ? $c->toDateString() : $from);
        }
        if ($to = $request->input('to_date')) {
            $c = Jalali::toCarbon($to);
            $query->whereDate('created_at', '<=', $c ? $c->toDateString() : $to);
        }

        // مرتب‌سازی
        $query->orderBy('created_at', $request->input('sort', 'newest') === 'oldest' ? 'asc' : 'desc');

        $users   = $query->paginate(24)->withQueryString();
        $userIds = $users->pluck('id')->toArray();

        $ticketStats = Ticket::withoutTenantIsolation()
            ->whereNull('site_id')
            ->whereIn('customer_id', $userIds)
            ->selectRaw('customer_id, COUNT(*) as total, SUM(status IN ("open","in_progress")) as active_count, MAX(updated_at) as last_activity')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        // آمار کلی
        $totalUsers    = User::where('is_admin', false)->count();
        $newToday      = User::where('is_admin', false)->whereDate('created_at', today())->count();
        $newThisMonth  = User::where('is_admin', false)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $usersWithActive = Ticket::withoutTenantIsolation()
            ->whereNull('site_id')
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');
        $totalTickets = Ticket::withoutTenantIsolation()
            ->whereNull('site_id')
            ->whereNotNull('customer_id')
            ->count();

        // روند ثبت‌نام ۳۰ روز گذشته
        $trendRaw = User::where('is_admin', false)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $trend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date    = now()->subDays($i)->format('Y-m-d');
            $trend[] = [
                'label' => now()->subDays($i)->format('m/d'),
                'count' => (int) ($trendRaw->get($date)?->count ?? 0),
            ];
        }

        return view('admin.users.index', compact(
            'users', 'ticketStats', 'totalUsers', 'usersWithActive',
            'newToday', 'newThisMonth', 'totalTickets', 'trend'
        ));
    }

    public function chat(Request $request, int $id)
    {
        $user = User::where('is_admin', false)->findOrFail($id);

        $allTickets = Ticket::withoutTenantIsolation()
            ->whereNull('site_id')
            ->where('customer_id', $user->id)
            ->orderByDesc('created_at')
            ->get(['id', 'ticket_number', 'subject', 'status', 'priority', 'created_at', 'updated_at']);

        $ticketId = (int) $request->query('ticket', 0);
        $ticket   = null;

        if ($ticketId) {
            $ticket = Ticket::withoutTenantIsolation()
                ->whereNull('site_id')
                ->where('customer_id', $user->id)
                ->where('id', $ticketId)
                ->with(['replies' => fn ($q) => $q->orderBy('created_at'), 'assignee'])
                ->first();
        }

        if (!$ticket) {
            $ticket = Ticket::withoutTenantIsolation()
                ->whereNull('site_id')
                ->where('customer_id', $user->id)
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
                ->with(['replies' => fn ($q) => $q->orderBy('created_at'), 'assignee'])
                ->orderByDesc('created_at')
                ->first();
        }

        $agentUsers = User::where('is_admin', true)->get(['id', 'name']);
        $smsEnabled = (bool) Setting::get('user_tickets.sms_enabled', false);

        return view('admin.users.chat', compact('user', 'ticket', 'allTickets', 'agentUsers', 'smsEnabled'));
    }

    public function startChat(Request $request, int $id)
    {
        $user = User::where('is_admin', false)->findOrFail($id);

        $ticket = Ticket::withoutTenantIsolation()->create([
            'ticket_number'   => 'UC-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
            'subject'         => 'گفتگو با ' . $user->name,
            'description'     => 'گفتگوی مستقیم آغاز‌شده از پنل کاربران',
            'priority'        => 'normal',
            'status'          => Ticket::STATUS_OPEN,
            'customer_id'     => $user->id,
            'customer_name'   => $user->name,
            'customer_mobile' => $user->mobile,
            'site_id'         => null,
        ]);

        $this->notifyUser($user, $ticket);

        return redirect()->route('admin.users.chat', ['id' => $user->id, 'ticket' => $ticket->id]);
    }

    private function notifyUser(User $user, Ticket $ticket): void
    {
        try {
            $mobile = $user->mobile ?? $user->phone ?? null;
            if ($mobile && Setting::get('user_tickets.sms_enabled', false)) {
                $this->sms->send($mobile,
                    'پشتیبانی یک گفتگو برای شما آغاز کرد: ' . $ticket->subject . ' — ' . $ticket->ticket_number
                );
            }
        } catch (\Throwable) {}
    }
}
