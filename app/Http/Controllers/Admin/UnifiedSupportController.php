<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use App\Services\SmsService;
use App\Services\SupportApiClient;
use App\Services\TicketConversationBridge;
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
        protected TicketConversationBridge $bridge,
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
            // تعداد درخواست‌های ویژگی باز در کانال پلتفرم
            'fr_open'       => $platformTickets->filter(fn($t) => $t->isFeatureRequest() && $t->status !== 'closed')->count(),
            'fr_total'      => $platformTickets->filter(fn($t) => $t->isFeatureRequest())->count(),
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

        // اگر در user-channel پیدا نشد (مثلاً تیکت site_id دارد)، platform را امتحان کن
        if (!$ticket && $channel === 'user') {
            $ticket  = $this->findPlatformTicket($id);
            $channel = $ticket ? 'platform' : 'user';
        }

        if (!$ticket) {
            abort(404);
        }

        $endUser    = ($channel === 'user' && ($ticket->customer_id ?? null))
            ? User::find((int) $ticket->customer_id)
            : null;
        $agentUsers = User::where('is_admin', true)->get(['id', 'name', 'mobile']);
        $smsEnabled = (bool) Setting::get('user_tickets.sms_enabled', false);
        $hasLicense = (bool) License::current()?->license_key;

        $sidebarTickets = Ticket::withoutTenantIsolation()
            ->whereNull('site_id')
            ->select(['id', 'ticket_number', 'subject', 'status', 'priority', 'customer_name', 'updated_at'])
            ->orderByDesc('updated_at')
            ->limit(60)
            ->get();

        return view('admin.unified-support.show', compact(
            'ticket', 'channel', 'id',
            'endUser', 'agentUsers', 'smsEnabled', 'hasLicense',
            'sidebarTickets'
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

    // ─── Bulk action (user-channel only) ────────────────────────────────────

    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids    = array_filter(array_map('intval', (array) $request->input('ids', [])));

        if (empty($ids) || !in_array($action, ['close', 'reopen', 'delete'], true)) {
            return back()->with('error', 'عملیات یا انتخاب معتبر نیست.');
        }

        // فقط تیکت‌های user-channel (site_id=null) مجاز هستند
        $tickets = Ticket::withoutTenantIsolation()->whereNull('site_id')->whereIn('id', $ids)->get();
        $count   = 0;

        foreach ($tickets as $ticket) {
            match ($action) {
                'close'  => $ticket->status !== 'closed'
                                ? $ticket->update(['status' => 'closed', 'closed_at' => now()])
                                : null,
                'reopen' => $ticket->status === 'closed'
                                ? $ticket->update(['status' => 'open', 'closed_at' => null])
                                : null,
                'delete' => $ticket->delete(),
            };
            $count++;
        }

        $labels = ['close' => 'بسته شدند', 'reopen' => 'بازگشایی شدند', 'delete' => 'حذف شدند'];
        return back()->with('success', "{$count} تیکت {$labels[$action]}.");
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
        $tickets = Ticket::withoutTenantIsolation()
            ->whereNull('site_id')
            ->with('assignee:id,name')
            ->orderByDesc('created_at')
            ->get();

        $ticketIds = $tickets->pluck('id');
        $lastAuthors = \SaaSPlatform\Modules\Tickets\Models\TicketReply::query()
            ->selectRaw('ticket_id, author_type')
            ->whereIn('ticket_id', $ticketIds)
            ->where('is_internal', false)
            ->orderByDesc('created_at')
            ->get()
            ->unique('ticket_id')
            ->pluck('author_type', 'ticket_id');

        return $tickets->map(function ($t) use ($lastAuthors) {
            $proxy                    = TicketProxyData::fromEloquent($t);
            $proxy->channel           = 'user';
            $proxy->last_reply_author = $lastAuthors->get($t->id);
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
            'type'     => ['nullable', 'in:support,feature_request'],
        ]);

        $ticketType    = $data['type'] ?? 'support';
        $uploadedFiles = $this->apiClient->uploadAttachments($request->file('attachments', []));
        $response      = $this->apiClient->store($license, [
            'subject'         => $data['subject'],
            'message'         => $data['message'],
            'priority'        => $data['priority'] ?? 'normal',
            'category'        => $data['category'] ?? null,
            'type'            => $ticketType,
            // برای درخواست ویژگی، از موبایل ادمین سایت برای شناسایی استفاده می‌شود
            'customer_mobile' => $ticketType === 'feature_request'
                ? (auth()->user()?->mobile ?? null)
                : null,
            'customer_name'   => $ticketType === 'feature_request'
                ? (auth()->user()?->name ?? null)
                : null,
        ], $uploadedFiles);

        if ($response['success'] ?? false) {
            $msg = ($ticketType === 'feature_request')
                ? 'درخواست ویژگی با موفقیت ارسال شد. پس از بررسی تیم، از طریق همین تیکت مطلع می‌شوید.'
                : 'تیکت به پلتفرم با موفقیت ارسال شد.';
            return redirect()->route('admin.inbox.index', ['channel' => 'platform'])
                ->with('success', $msg);
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

        $this->bridge->syncNewTicket(
            ticket: $ticket,
            creatorId: (string) auth()->id(),
            creatorType: 'admin',
            targetUserId: $targetUser ? (string) $targetUser->id : null,
        );

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
        $uploadedFiles = $request->file('attachments', []);
        if (!is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        foreach ($uploadedFiles as $file) {
            if (!$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid() || $file->getSize() === 0) {
                continue;
            }
            try {
                $subdir   = 'tickets/' . now()->format('Y/m');
                $destDir  = public_path('uploads/' . $subdir);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0775, true);
                }
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
                \Illuminate\Support\Facades\Log::error('UnifiedSupportController::replyUser file upload error: ' . $e->getMessage());
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
