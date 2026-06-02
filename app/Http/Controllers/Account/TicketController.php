<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SmsService;
use App\Services\TicketConversationBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SaaSPlatform\Modules\Tickets\Models\Ticket;
use SaaSPlatform\Modules\Tickets\Models\TicketReply;

class TicketController extends Controller
{
    public function __construct(
        protected SmsService $sms,
        protected TicketConversationBridge $bridge,
    ) {}

    public function index(Request $request)
    {
        $tickets = Ticket::withoutTenantIsolation()
            ->where('customer_id', auth()->id())
            ->whereNull('site_id')
            ->with('assignee:id,name')
            ->withCount([
                'replies as staff_replies_count' => fn($q) => $q->where('author_type', 'staff')->where('is_internal', false),
            ])
            ->orderByDesc('created_at')
            ->paginate(15);

        $stats = [
            'total'       => Ticket::withoutTenantIsolation()->where('customer_id', auth()->id())->whereNull('site_id')->count(),
            'open'        => Ticket::withoutTenantIsolation()->where('customer_id', auth()->id())->whereNull('site_id')->where('status', 'open')->count(),
            'in_progress' => Ticket::withoutTenantIsolation()->where('customer_id', auth()->id())->whereNull('site_id')->where('status', 'in_progress')->count(),
            'closed'      => Ticket::withoutTenantIsolation()->where('customer_id', auth()->id())->whereNull('site_id')->where('status', 'closed')->count(),
        ];

        $siteName = \App\Helpers\SiteHelper::siteName();

        return view('account.tickets.index', compact('tickets', 'stats', 'siteName'));
    }

    public function create()
    {
        return view('account.tickets.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'     => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority'    => ['nullable', 'in:low,normal,high'],
            'category'    => ['nullable', 'string', 'max:100'],
        ]);

        $user   = auth()->user();
        $ticket = Ticket::withoutTenantIsolation()->create([
            'ticket_number'  => $this->generateNumber(),
            'subject'        => $data['subject'],
            'description'    => $data['description'],
            'priority'       => $data['priority'] ?? 'normal',
            'category'       => $data['category'] ?? null,
            'status'         => Ticket::STATUS_OPEN,
            'customer_id'    => $user->id,
            'customer_name'   => $user->name,
            'customer_mobile' => $user->mobile ?? null,
            'site_id'        => null,
        ]);

        $this->bridge->syncNewTicket(
            ticket: $ticket,
            creatorId: (string) $user->id,
            creatorType: 'user',
        );

        $this->notifyAdmin($ticket, 'تیکت جدید');

        return redirect()->route('account.tickets.show', $ticket->id)
            ->with('success', 'تیکت شما با موفقیت ثبت شد.');
    }

    public function show(int $id)
    {
        $ticket = Ticket::withoutTenantIsolation()
            ->where('customer_id', auth()->id())
            ->whereNull('site_id')
            ->with(['replies' => fn($q) => $q->where('is_internal', false)->orderBy('created_at')])
            ->findOrFail($id);

        $allTickets = Ticket::withoutTenantIsolation()
            ->where('customer_id', auth()->id())
            ->whereNull('site_id')
            ->orderByDesc('updated_at')
            ->get();

        return view('account.tickets.show', compact('ticket', 'allTickets'));
    }

    public function poll(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::withoutTenantIsolation()
            ->where('customer_id', auth()->id())
            ->whereNull('site_id')
            ->findOrFail($id);

        $lastId = (int) $request->query('last_id', 0);

        $replies = TicketReply::where('ticket_id', $ticket->id)
            ->where('is_internal', false)
            ->where('id', '>', $lastId)
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'replies' => $replies->map(fn($r) => [
                'id'          => $r->id,
                'message'     => $r->message,
                'author_type' => $r->author_type,
                'author_name' => $r->user?->name ?? ($r->author_type === 'staff' ? 'پشتیبانی' : ($r->customer_name ?? 'شما')),
                'created_at'  => $r->created_at->toIso8601String(),
                'attachments' => is_array($r->attachments) ? $r->attachments : [],
            ])->values(),
            'last_id' => $replies->last()?->id ?? $lastId,
        ]);
    }

    public function reply(Request $request, int $id)
    {
        $ticket = Ticket::withoutTenantIsolation()
            ->where('customer_id', auth()->id())
            ->whereNull('site_id')
            ->findOrFail($id);

        if ($ticket->status === Ticket::STATUS_CLOSED) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'تیکت بسته شده است.'], 422);
            }
            return back()->with('error', 'تیکت بسته شده است.');
        }

        $data = $request->validate([
            'message'       => ['required', 'string', 'max:5000'],
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
                \Illuminate\Support\Facades\Log::error('TicketController::reply file upload error: ' . $e->getMessage());
            }
        }

        $reply = TicketReply::create([
            'ticket_id'     => $ticket->id,
            'message'       => $data['message'],
            'author_type'   => TicketReply::AUTHOR_CUSTOMER,
            'is_internal'   => false,
            'customer_name'   => auth()->user()->name,
            'customer_mobile' => auth()->user()->mobile ?? null,
            'attachments'   => $attachments ?: null,
        ]);

        $this->bridge->syncReply(
            reply: $reply,
            ticket: $ticket,
            senderId: (string) auth()->id(),
            senderType: 'user',
            attachments: $attachments,
        );

        $this->notifyAdmin($ticket, 'پاسخ کاربر');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'reply' => [
                    'id'          => $reply->id,
                    'message'     => $reply->message,
                    'author_type' => $reply->author_type,
                    'author_name' => auth()->user()->name ?? 'شما',
                    'is_internal' => false,
                    'created_at'  => $reply->created_at->toIso8601String(),
                    'attachments' => $attachments,
                ],
            ]);
        }

        return back()->with('success', 'پیام شما ارسال شد.');
    }

    public function checkActivity(Request $request): JsonResponse
    {
        $lastId    = (int) $request->query('last_id', 0);
        $ticketIds = Ticket::withoutTenantIsolation()
            ->where('customer_id', auth()->id())
            ->whereNull('site_id')
            ->pluck('id');

        if ($ticketIds->isEmpty()) {
            return response()->json(['new_count' => 0, 'last_id' => $lastId, 'replies' => []]);
        }

        $replies = TicketReply::whereIn('ticket_id', $ticketIds)
            ->where('author_type', TicketReply::AUTHOR_STAFF)
            ->where('is_internal', false)
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->with('ticket:id,ticket_number,subject')
            ->get();

        return response()->json([
            'new_count' => $replies->count(),
            'last_id'   => $replies->last()?->id ?? $lastId,
            'replies'   => $replies->map(fn($r) => [
                'id'            => $r->id,
                'ticket_id'     => $r->ticket_id,
                'ticket_number' => $r->ticket?->ticket_number ?? '#',
                'subject'       => $r->ticket?->subject ?? 'تیکت',
                'url'           => route('account.tickets.show', $r->ticket_id),
            ])->values(),
        ]);
    }

    private function generateNumber(): string
    {
        return 'UT-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
    }

    private function notifyAdmin(Ticket $ticket, string $event): void
    {
        try {
            $adminPhone = Setting::get('user_tickets.admin_mobile')
                       ?? Setting::get('admin_mobile')
                       ?? null;
            if ($adminPhone && Setting::get('user_tickets.sms_enabled', false)) {
                $this->sms->send($adminPhone,
                    "{$event}: تیکت {$ticket->ticket_number} از طرف {$ticket->customer_name}"
                );
            }
        } catch (\Throwable) {}
    }
}
