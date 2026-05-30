<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SaaSPlatform\Modules\Tickets\Models\Ticket;

class UserPanelController extends Controller
{
    public function __construct(protected SmsService $sms) {}

    public function index(Request $request)
    {
        $query = User::where('is_admin', false)->orderByDesc('created_at');

        if ($search = $request->input('q')) {
            $query->where(function ($b) use ($search) {
                $b->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(24)->withQueryString();
        $userIds = $users->pluck('id')->toArray();

        $ticketStats = Ticket::withoutTenantIsolation()
            ->whereNull('site_id')
            ->whereIn('customer_id', $userIds)
            ->selectRaw('customer_id, COUNT(*) as total, SUM(status IN ("open","in_progress")) as active_count, MAX(updated_at) as last_activity')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $totalUsers = User::where('is_admin', false)->count();
        $usersWithActive = Ticket::withoutTenantIsolation()
            ->whereNull('site_id')
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        return view('admin.users.index', compact('users', 'ticketStats', 'totalUsers', 'usersWithActive'));
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
            'ticket_number'  => 'UC-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
            'subject'        => 'گفتگو با ' . $user->name,
            'description'    => 'گفتگوی مستقیم آغاز‌شده از پنل کاربران',
            'priority'       => 'normal',
            'status'         => Ticket::STATUS_OPEN,
            'customer_id'    => $user->id,
            'customer_name'  => $user->name,
            'customer_email' => $user->email,
            'site_id'        => null,
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
