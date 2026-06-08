<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\SmsService;
use App\Services\TicketConversationBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SaaSPlatform\Modules\Tickets\Models\Ticket;
use SaaSPlatform\Modules\Tickets\Models\TicketReply;

/**
 * Admin panel for managing tickets between site admin/agents and end users.
 */
class UserTicketController extends Controller
{
    public function __construct(
        protected SmsService $sms,
        protected TicketConversationBridge $bridge,
    ) {}

    public function index(Request $request)
    {
        $query = Ticket::withoutTenantIsolation()->whereNull('site_id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }
        if ($q = $request->input('q')) {
            $query->where(function ($b) use ($q) {
                $b->where('subject', 'like', "%{$q}%")
                  ->orWhere('customer_name', 'like', "%{$q}%")
                  ->orWhere('ticket_number', 'like', "%{$q}%");
            });
        }
        if ($userId = $request->input('user_id')) {
            $user = User::find($userId);
            if ($user) {
                $query->where(function ($b) use ($user) {
                    $b->where('customer_id', $user->id);
                    if ($user->mobile) {
                        $b->orWhere('customer_mobile', $user->mobile);
                    }
                });
            }
        }

        $tickets = $query->with(['assignee'])->orderByDesc('created_at')->paginate(20)->withQueryString();

        $lastReplyAuthors = TicketReply::query()
            ->selectRaw('ticket_id, author_type')
            ->whereIn('ticket_id', $tickets->pluck('id'))
            ->where('is_internal', false)
            ->orderByDesc('created_at')
            ->get()
            ->unique('ticket_id')
            ->pluck('author_type', 'ticket_id');

        $allTickets = Ticket::withoutTenantIsolation()->whereNull('site_id')
            ->with('assignee:id,name')
            ->get(['id', 'status', 'priority', 'assigned_to']);

        $agentUsers  = User::where('is_admin', true)->get(['id', 'name']);
        $regularUsers = User::where('is_admin', false)
            ->orderBy('name')->get(['id', 'name', 'mobile']);

        $stats = [
            'total'            => $allTickets->count(),
            'open'             => $allTickets->where('status', 'open')->count(),
            'in_progress'      => $allTickets->where('status', 'in_progress')->count(),
            'closed'           => $allTickets->where('status', 'closed')->count(),
            'avg_response_hours' => 4,
        ];

        return view('admin.user-tickets.index', compact('tickets', 'stats', 'allTickets', 'agentUsers', 'regularUsers', 'lastReplyAuthors'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'mobile']);
        return view('admin.user-tickets.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'for_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'subject'     => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority'    => ['nullable', 'in:low,normal,high'],
            'category'    => ['nullable', 'string', 'max:100'],
            'send_sms'    => ['nullable', 'boolean'],
        ]);

        $targetUser = !empty($data['for_user_id']) ? User::find($data['for_user_id']) : null;

        $ticket = Ticket::withoutTenantIsolation()->create([
            'ticket_number'  => $this->generateNumber(),
            'subject'        => $data['subject'],
            'description'    => $data['description'],
            'priority'       => $data['priority'] ?? 'normal',
            'category'       => $data['category'] ?? null,
            'status'         => Ticket::STATUS_OPEN,
            'customer_id'    => $targetUser?->id,
            'customer_name'   => $targetUser?->name ?? 'ادمین',
            'customer_mobile' => $targetUser?->mobile ?? null,
            'site_id'        => null,
        ]);

        $this->bridge->syncNewTicket(
            ticket: $ticket,
            creatorId: (string) auth()->id(),
            creatorType: 'admin',
            targetUserId: $targetUser ? (string) $targetUser->id : null,
        );

        if ($targetUser && $request->boolean('send_sms')) {
            $this->notifyUser($targetUser, $ticket, 'تیکت جدید از طرف پشتیبانی');
        }

        return redirect()->route('admin.user-tickets.show', $ticket->id)
            ->with('success', 'تیکت با موفقیت ایجاد شد.');
    }

    public function show(int $id)
    {
        $ticket = Ticket::withoutTenantIsolation()
            ->whereNull('site_id')
            ->with(['replies' => fn($q) => $q->orderBy('created_at'), 'assignee'])
            ->findOrFail($id);

        $endUser    = $ticket->customer_id ? User::find($ticket->customer_id) : null;
        $agentUsers = User::where('is_admin', true)->get(['id', 'name', 'mobile']);
        $smsEnabled = (bool) Setting::get('user_tickets.sms_enabled', false);

        return view('admin.user-tickets.show', compact('ticket', 'endUser', 'agentUsers', 'smsEnabled'));
    }

    public function poll(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::withoutTenantIsolation()->whereNull('site_id')->findOrFail($id);
        $lastId = (int) $request->query('last_id', 0);

        $replies = TicketReply::where('ticket_id', $ticket->id)
            ->where('id', '>', $lastId)
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'replies' => $replies->map(fn($r) => [
                'id'          => $r->id,
                'message'     => $r->message,
                'author_type' => $r->author_type,
                'author_name' => $r->user?->name ?? ($r->author_type === 'staff' ? 'پشتیبان' : ($r->customer_name ?? 'کاربر')),
                'is_internal' => (bool) $r->is_internal,
                'created_at'  => $r->created_at->toIso8601String(),
            ])->values(),
            'last_id' => $replies->last()?->id ?? $lastId,
        ]);
    }

    public function reply(Request $request, int $id)
    {
        $ticket = Ticket::withoutTenantIsolation()->whereNull('site_id')->findOrFail($id);

        $data = $request->validate([
            'message'       => ['required', 'string', 'max:5000'],
            'send_sms'      => ['nullable', 'boolean'],
            'is_internal'   => ['nullable', 'boolean'],
            'attachments'   => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $attachments = [];
        $uploadedFiles = $request->file('attachments', []);
        if (!is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }
        foreach ($uploadedFiles as $file) {
            if (!$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid() || $file->getSize() === 0) continue;
            try {
                $subdir   = 'tickets/' . now()->format('Y/m');
                $destDir  = public_path('uploads/' . $subdir);
                if (!is_dir($destDir)) { mkdir($destDir, 0775, true); }
                $ext      = $file->guessExtension() ?: ($file->getClientOriginalExtension() ?: 'bin');
                $origName = $file->getClientOriginalName();
                $origMime = $file->getMimeType();
                $origSize = $file->getSize();
                $filename = \Illuminate\Support\Str::random(32) . '.' . $ext;
                $file->move($destDir, $filename);
                $attachments[] = [
                    'name' => $origName,
                    'path' => 'uploads/' . $subdir . '/' . $filename,
                    'url'  => url('uploads/' . $subdir . '/' . $filename),
                    'size' => $origSize,
                    'mime' => $origMime,
                ];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('UserTicketController file upload error: ' . $e->getMessage());
            }
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

        $this->bridge->syncReply(
            reply: $reply,
            ticket: $ticket,
            senderId: (string) auth()->id(),
            senderType: 'admin',
            attachments: $attachments,
        );

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

    public function updateStatus(Request $request, int $id)
    {
        $ticket = Ticket::withoutTenantIsolation()->whereNull('site_id')->findOrFail($id);
        $status = $request->validate(['status' => ['required', 'in:open,in_progress,closed']])['status'];

        $ticket->update([
            'status'    => $status,
            'closed_at' => $status === 'closed' ? now() : null,
        ]);

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
        return back()->with('success', 'اولویت تیکت بروزرسانی شد.');
    }

    public function checkNew(Request $request): JsonResponse
    {
        $lastId  = (int) $request->query('last_id', 0);
        $tickets = Ticket::withoutTenantIsolation()
            ->whereNull('site_id')
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->get(['id', 'ticket_number', 'subject', 'customer_name', 'created_at']);

        return response()->json([
            'new_count' => $tickets->count(),
            'last_id'   => $tickets->last()?->id ?? $lastId,
            'tickets'   => $tickets->map(fn($t) => [
                'id'            => $t->id,
                'ticket_number' => $t->ticket_number,
                'subject'       => $t->subject,
                'customer_name' => $t->customer_name,
                'url'           => route('admin.user-tickets.show', $t->id),
            ])->values(),
        ]);
    }

    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids    = array_filter(array_map('intval', (array) $request->input('ids', [])));

        if (empty($ids) || !in_array($action, ['close', 'reopen', 'delete'], true)) {
            return back()->with('error', 'عملیات یا انتخاب معتبر نیست.');
        }

        $tickets = Ticket::withoutTenantIsolation()->whereNull('site_id')->whereIn('id', $ids)->get();
        $count   = 0;

        foreach ($tickets as $ticket) {
            match ($action) {
                'close'  => $this->applyClose($ticket),
                'reopen' => $this->applyReopen($ticket),
                'delete' => $ticket->delete(),
            };
            $count++;
        }

        $labels = ['close' => 'بسته شدند', 'reopen' => 'بازگشایی شدند', 'delete' => 'حذف شدند'];
        return back()->with('success', "{$count} تیکت {$labels[$action]}.");
    }

    private function applyClose(Ticket $ticket): void
    {
        if ($ticket->status !== 'closed') {
            $ticket->update(['status' => 'closed', 'closed_at' => now()]);
        }
    }

    private function applyReopen(Ticket $ticket): void
    {
        if ($ticket->status === 'closed') {
            $ticket->update(['status' => 'open', 'closed_at' => null]);
        }
    }

    public function destroy(int $id)
    {
        $ticket = Ticket::withoutTenantIsolation()->whereNull('site_id')->findOrFail($id);
        $number = $ticket->ticket_number;
        $ticket->delete();

        return redirect()->route('admin.user-tickets.index')
            ->with('success', 'تیکت #' . $number . ' با موفقیت حذف شد.');
    }

    private function generateNumber(): string
    {
        return 'UT-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
    }

    private function notifyUser(User $user, Ticket $ticket, string $message): void
    {
        try {
            $mobile = $user->mobile ?? $user->phone ?? null;
            if ($mobile && Setting::get('user_tickets.sms_enabled', true)) {
                $this->sms->send($mobile,
                    "{$message}: {$ticket->ticket_number} — {$ticket->subject}"
                );
            }
        } catch (\Throwable) {}

        // Push notification به کاربر
        try {
            $pushService = app(\App\Services\PushNotificationService::class);
            $pushService->sendToUser(
                $user->id,
                'پاسخ جدید به تیکت شما',
                "{$ticket->ticket_number}: " . str($ticket->subject)->limit(60),
                route('account.tickets.show', $ticket->id)
            );
        } catch (\Throwable) {}
    }
}
