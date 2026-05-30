<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use App\Services\SmsService;
use App\Services\SupportApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SaaSPlatform\Modules\Tickets\Dto\TicketProxyData;
use SaaSPlatform\Modules\Tickets\Models\Ticket;
use SaaSPlatform\Modules\Tickets\Models\TicketReply;

/**
 * Unified Support Inbox — admin/inbox/*
 *
 * Merges two communication channels into one view:
 *   channel=platform  →  admin ↔ saas-platform (via SupportApiClient HTTP)
 *   channel=user      →  admin ↔ end-user (direct DB, site_id=null)
 */
class UnifiedSupportController extends Controller
{
    public function __construct(
        protected SupportApiClient $apiClient,
        protected SmsService $sms,
    ) {}

    // ─── Index ──────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $channel = $request->input('channel', 'all');
        $status  = $request->input('status', '');
        $q       = $request->input('q', '');

        [$platformTickets, $platformError] = $this->loadPlatformTickets();
        $userTickets = $this->loadUserTickets();

        $all = match ($channel) {
            'platform' => $platformTickets,
            'user'     => $userTickets,
            default    => $platformTickets->merge($userTickets)->sortByDesc('created_at')->values(),
        };

        if ($status) {
            $all = $all->where('status', $status)->values();
        }
        if ($q) {
            $qLower = mb_strtolower($q);
            $all = $all->filter(fn($t) =>
                str_contains(mb_strtolower($t->subject), $qLower) ||
                str_contains($t->ticket_number, $q) ||
                str_contains(mb_strtolower((string) $t->customer_name), $qLower)
            )->values();
        }

        $stats = [
            'total'         => $all->count(),
            'open'          => $all->where('status', 'open')->count(),
            'in_progress'   => $all->where('status', 'in_progress')->count(),
            'closed'        => $all->where('status', 'closed')->count(),
            'platform_open' => $platformTickets->where('status', 'open')->count(),
            'platform_total'=> $platformTickets->count(),
            'user_open'     => $userTickets->where('status', 'open')->count(),
            'user_total'    => $userTickets->count(),
        ];

        $hasLicense = (bool) License::current()?->license_key;

        return view('admin.unified-support.index', compact(
            'all', 'stats', 'channel', 'status', 'q',
            'platformError', 'hasLicense'
        ));
    }

    // ─── Create ─────────────────────────────────────────────────────────────

    public function create()
    {
        $users      = User::orderBy('name')->get(['id', 'name', 'mobile']);
        $hasLicense = (bool) License::current()?->license_key;

        return view('admin.unified-support.create', compact('users', 'hasLicense'));
    }

    public function store(Request $request)
    {
        $channel = $request->input('channel', 'user');

        return $channel === 'platform'
            ? $this->storePlatform($request)
            : $this->storeUser($request);
    }

    // ─── Show ────────────────────────────────────────────────────────────────

    public function show(string $channel, int $id)
    {
        $ticket = $channel === 'platform'
            ? $this->findPlatformTicket($id)
            : $this->findUserTicket($id);

        if (!$ticket) {
            abort(404);
        }

        $endUser    = ($channel === 'user' && ($ticket->customer_id ?? null))
            ? User::find((int) $ticket->customer_id)
            : null;
        $agentUsers = User::where('is_admin', true)->get(['id', 'name', 'email', 'mobile']);
        $smsEnabled = (bool) Setting::get('user_tickets.sms_enabled', false);
        $hasLicense = (bool) License::current()?->license_key;

        return view('admin.unified-support.show', compact(
            'ticket', 'channel', 'id',
            'endUser', 'agentUsers', 'smsEnabled', 'hasLicense'
        ));
    }

    // ─── Poll ────────────────────────────────────────────────────────────────

    public function poll(Request $request, string $channel, int $id): JsonResponse
    {
        $lastId = (int) $request->query('last_id', 0);

        if ($channel === 'platform') {
            $license = License::current();
            if (!$license?->license_key) {
                return response()->json(['replies' => [], 'last_id' => $lastId]);
            }
            $result = $this->apiClient->poll($license, $id, $lastId);
            return response()->json(['replies' => $result['replies'], 'last_id' => $result['last_id']]);
        }

        $ticket = Ticket::withoutTenantIsolation()->whereNull('site_id')->findOrFail($id);
        $replies = TicketReply::where('ticket_id', $ticket->id)
            ->where('id', '>', $lastId)
            ->orderBy('created_at')
            ->with('user:id,name')
            ->get();

        return response()->json([
            'replies' => $replies->map(fn($r) => [
                'id'          => $r->id,
                'message'     => $r->message,
                'author_type' => $r->author_type,
                'author_name' => $r->user?->name ?? ($r->author_type === 'staff' ? 'پشتیبان' : ($r->customer_name ?? 'کاربر')),
                'is_internal' => (bool) $r->is_internal,
                'created_at'  => $r->created_at->toIso8601String(),
                'attachments' => $r->attachments ?? [],
            ])->values(),
            'last_id' => $replies->last()?->id ?? $lastId,
        ]);
    }

    // ─── Reply ───────────────────────────────────────────────────────────────

    public function reply(Request $request, string $channel, int $id)
    {
        return $channel === 'platform'
            ? $this->replyPlatform($request, $id)
            : $this->replyUser($request, $id);
    }

    // ─── User-ticket specific controls ──────────────────────────────────────

    public function updateStatus(Request $request, int $id)
    {
        $ticket = Ticket::withoutTenantIsolation()->whereNull('site_id')->findOrFail($id);
        $status = $request->validate(['status' => ['required', 'in:open,in_progress,closed']])['status'];
        $ticket->update(['status' => $status, 'closed_at' => $status === 'closed' ? now() : null]);

        return back()->with('success', 'وضعیت تیکت بروزرسانی شد.');
    }

    public function assign(Request $request, int $id)
    {
        $ticket = Ticket::withoutTenantIsolation()->whereNull('site_id')->findOrFail($id);
        $data   = $request->validate(['assigned_to' => ['nullable', 'integer', 'exists:users,id']]);
        $ticket->update(['assigned_to' => $data['assigned_to'] ?: null]);

        return back()->with('success', 'تخصیص کارشناس ثبت شد.');
    }

    public function priority(Request $request, int $id)
    {
        $ticket = Ticket::withoutTenantIsolation()->whereNull('site_id')->findOrFail($id);
        $data   = $request->validate(['priority' => ['required', 'in:low,normal,high']]);
        $ticket->update(['priority' => $data['priority']]);

        return back()->with('success', 'اولویت بروزرسانی شد.');
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function loadPlatformTickets(): array
    {
        $license = License::current();
        $error   = null;
        $tickets = collect();

        if ($license?->license_key && $license?->domain) {
            $result  = $this->apiClient->index($license);
            $tickets = TicketProxyData::collectionFromApiResult($result['tickets'])
                ->each(fn($t) => $t->channel = 'platform');
            $error   = $result['error'];
        } else {
            $error = 'لایسنس فعالی یافت نشد.';
        }

        return [$tickets, $error];
    }

    private function loadUserTickets(): \Illuminate\Support\Collection
    {
        return Ticket::withoutTenantIsolation()
            ->whereNull('site_id')
            ->with('assignee:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($t) {
                $proxy          = TicketProxyData::fromEloquent($t);
                $proxy->channel = 'user';
                return $proxy;
            });
    }

    private function findPlatformTicket(int $id): ?TicketProxyData
    {
        $license = License::current();
        if (!$license?->license_key) {
            return null;
        }

        $result = $this->apiClient->find($license, $id);
        if (!$result['ticket']) {
            return null;
        }

        $ticket          = TicketProxyData::fromArray(is_array($result['ticket']) ? $result['ticket'] : (array) $result['ticket']);
        $ticket->channel = 'platform';
        return $ticket;
    }

    private function findUserTicket(int $id): ?TicketProxyData
    {
        $model = Ticket::withoutTenantIsolation()
            ->whereNull('site_id')
            ->with(['replies' => fn($q) => $q->with('user:id,name')->orderBy('created_at'), 'assignee:id,name'])
            ->find($id);

        if (!$model) {
            return null;
        }

        $proxy          = TicketProxyData::fromEloquent($model);
        $proxy->channel = 'user';
        return $proxy;
    }

    private function storePlatform(Request $request)
    {
        $license = License::current();
        if (!$license?->license_key) {
            return back()->with('error', 'لایسنس فعالی یافت نشد.')->withInput();
        }

        $data = $request->validate([
            'subject'  => ['required', 'string', 'max:255'],
            'message'  => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'in:low,normal,high'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $uploadedFiles = $this->apiClient->uploadAttachments($request->file('attachments', []));
        $response = $this->apiClient->store($license, [
            'subject'  => $data['subject'],
            'message'  => $data['message'],
            'priority' => $data['priority'] ?? 'normal',
            'category' => $data['category'] ?? null,
        ], $uploadedFiles);

        if ($response['success'] ?? false) {
            return redirect()->route('admin.inbox.index', ['channel' => 'platform'])
                ->with('success', 'تیکت به پلتفرم با موفقیت ارسال شد.');
        }

        return back()->with('error', $response['message'] ?? 'خطا در ثبت تیکت.')->withInput();
    }

    private function storeUser(Request $request)
    {
        $data = $request->validate([
            'for_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'subject'     => ['required', 'string', 'max:255'],
            'message'     => ['required', 'string', 'max:5000'],
            'priority'    => ['nullable', 'in:low,normal,high'],
            'category'    => ['nullable', 'string', 'max:100'],
            'send_sms'    => ['nullable', 'boolean'],
        ]);

        $targetUser = !empty($data['for_user_id']) ? User::find($data['for_user_id']) : null;

        $ticket = Ticket::withoutTenantIsolation()->create([
            'ticket_number'  => 'UT-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
            'subject'        => $data['subject'],
            'description'    => $data['message'],
            'priority'       => $data['priority'] ?? 'normal',
            'category'       => $data['category'] ?? null,
            'status'         => Ticket::STATUS_OPEN,
            'customer_id'    => $targetUser?->id,
            'customer_name'   => $targetUser?->name ?? 'ادمین',
            'customer_mobile' => $targetUser?->mobile,
            'site_id'        => null,
        ]);

        if ($targetUser && $request->boolean('send_sms')) {
            $this->notifyUser($targetUser, $ticket, 'تیکت جدید از طرف پشتیبانی');
        }

        return redirect()->route('admin.inbox.show', ['channel' => 'user', 'id' => $ticket->id])
            ->with('success', 'تیکت با موفقیت ایجاد شد.');
    }

    private function replyPlatform(Request $request, int $id)
    {
        $license = License::current();
        if (!$license?->license_key) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'لایسنس فعالی یافت نشد.'], 422)
                : back()->with('error', 'لایسنس فعالی یافت نشد.');
        }

        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);
        $uploadedFiles = $this->apiClient->uploadAttachments($request->file('attachments', []));

        $response = $this->apiClient->reply($license, $id, [
            'message'       => $data['message'],
            'customer_name' => auth()->user()?->name ?? 'کارشناس',
        ], $uploadedFiles);

        if ($request->wantsJson() || $request->ajax()) {
            if ($response['success'] ?? false) {
                $replies   = $response['ticket']['replies'] ?? [];
                $lastReply = !empty($replies) ? end($replies) : null;
                return response()->json(['success' => true, 'reply' => $lastReply ?: null]);
            }
            return response()->json(['success' => false, 'message' => $response['message'] ?? 'خطا'], 422);
        }

        return ($response['success'] ?? false)
            ? back()->with('success', 'پاسخ ثبت شد.')
            : back()->with('error', $response['message'] ?? 'خطا در ثبت پاسخ.')->withInput();
    }

    private function replyUser(Request $request, int $id)
    {
        $ticket = Ticket::withoutTenantIsolation()->whereNull('site_id')->findOrFail($id);

        $data = $request->validate([
            'message'       => ['required', 'string', 'max:5000'],
            'is_internal'   => ['nullable', 'boolean'],
            'send_sms'      => ['nullable', 'boolean'],
            'attachments'   => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $attachments = [];
        foreach ($request->file('attachments', []) as $file) {
            if (!$file->isValid() || $file->getSize() === 0) {
                continue;
            }
            $path = $file->store('support-tickets/' . now()->format('Y/m'), 'public');
            if (!$path) {
                continue;
            }
            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'url'  => asset(Storage::url($path)),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        }

        $isInternal = $request->boolean('is_internal', false);

        $reply = TicketReply::create([
            'ticket_id'   => $ticket->id,
            'message'     => $data['message'],
            'author_type' => TicketReply::AUTHOR_STAFF,
            'is_internal' => $isInternal,
            'user_id'     => auth()->id(),
            'attachments' => $attachments ?: null,
        ]);

        if (!$isInternal && $ticket->status === Ticket::STATUS_OPEN) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        if (!$isInternal && $request->boolean('send_sms') && $ticket->customer_id) {
            $endUser = User::find($ticket->customer_id);
            if ($endUser) {
                $this->notifyUser($endUser, $ticket, 'پاسخ جدید به تیکت شما');
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'reply' => [
                    'id'          => $reply->id,
                    'message'     => $reply->message,
                    'author_type' => $reply->author_type,
                    'author_name' => auth()->user()?->name ?? 'پشتیبان',
                    'is_internal' => $isInternal,
                    'created_at'  => $reply->created_at->toIso8601String(),
                    'attachments' => $attachments,
                ],
            ]);
        }

        return back()->with('success', 'پاسخ ثبت شد.');
    }

    private function notifyUser(User $user, Ticket $ticket, string $message): void
    {
        try {
            $mobile = $user->mobile ?? $user->phone ?? null;
            if ($mobile && Setting::get('user_tickets.sms_enabled', false)) {
                $this->sms->send($mobile, "{$message}: {$ticket->ticket_number} — {$ticket->subject}");
            }
        } catch (\Throwable) {}
    }
}
