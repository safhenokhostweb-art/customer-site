<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\User;
use App\Services\TicketConversationBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use SaaSPlatform\Modules\Tickets\Models\Ticket;
use SaaSPlatform\Modules\Tickets\Models\TicketReply;

class CustomerMobileTicketController extends Controller
{
    public function __construct(
        protected TicketConversationBridge $bridge,
    ) {}

    // ── GET /api/mobile/tickets ──────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Ticket::withoutTenantIsolation()->whereNull('site_id');

        if (!($user->is_admin ?? false)) {
            $query->where(function ($q) use ($user) {
                $q->where('customer_id', $user->id);
                if ($user->mobile) {
                    $q->orWhere('customer_mobile', $user->mobile);
                }
            });
        }

        $tickets = $query->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'data' => $tickets->getCollection()->map(fn($t) => $this->summary($t))->values(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page'    => $tickets->lastPage(),
                'total'        => $tickets->total(),
                'per_page'     => $tickets->perPage(),
                'role'         => ($request->user()->is_admin ?? false) ? 'admin' : 'end_user',
            ],
        ]);
    }

    // ── GET /api/mobile/tickets/{id} ─────────────────────────────────────────

    public function show(Request $request, int $id): JsonResponse
    {
        $user   = $request->user();
        $ticket = $this->findForUser($id, $user);

        if (!$ticket) {
            return response()->json(['message' => 'تیکت یافت نشد.'], 404);
        }

        return response()->json(['data' => $this->detail($ticket)]);
    }

    // ── POST /api/mobile/tickets ─────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        $validated = $request->validate([
            'subject'     => 'required|string|max:255',
            'description' => 'required|string',
            'priority'    => 'nullable|in:low,normal,high',
            'category'    => 'nullable|string|max:100',
        ]);

        $ticket = Ticket::withoutTenantIsolation()->create([
            'ticket_number'  => 'UT-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
            'subject'        => $validated['subject'],
            'description'    => $validated['description'],
            'priority'       => $validated['priority'] ?? Ticket::PRIORITY_NORMAL,
            'category'       => $validated['category'] ?? null,
            'status'         => Ticket::STATUS_OPEN,
            'customer_id'    => $user->id,
            'customer_name'  => $user->name ?? $user->mobile,
            'customer_mobile' => $user->mobile,
            'site_id'        => null,
        ]);

        $this->bridge->syncNewTicket(
            ticket: $ticket,
            creatorId: (string) $user->id,
            creatorType: 'user',
        );

        return response()->json([
            'data'    => $this->detail($ticket),
            'message' => 'تیکت با موفقیت ثبت شد.',
        ], 201);
    }

    // ── POST /api/mobile/tickets/{id}/reply ──────────────────────────────────

    public function reply(Request $request, int $id): JsonResponse
    {
        $user   = $request->user();
        $ticket = $this->findForUser($id, $user);

        if (!$ticket) {
            return response()->json(['message' => 'تیکت یافت نشد.'], 404);
        }

        if ($ticket->status === Ticket::STATUS_CLOSED) {
            return response()->json(['message' => 'این تیکت بسته شده است.'], 422);
        }

        $validated = $request->validate([
            'message' => 'nullable|string',
        ]);

        if (empty($validated['message']) && !$request->hasFile('files')) {
            return response()->json(['message' => 'پیام الزامی است.'], 422);
        }

        // پردازش فایل‌های پیوست
        $attachments    = [];
        $uploadedFiles  = $request->hasFile('files') ? (array) $request->file('files') : [];
        foreach ($uploadedFiles as $file) {
            if (!$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid()) {
                continue;
            }
            try {
                $subdir   = 'tickets/' . now()->format('Y/m');
                $destDir  = public_path('uploads/' . $subdir);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0775, true);
                }
                $filename = Str::random(32) . '.' . ($file->guessExtension() ?: 'bin');
                $file->move($destDir, $filename);
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => 'uploads/' . $subdir . '/' . $filename,
                    'url'  => url('uploads/' . $subdir . '/' . $filename),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType() ?? 'application/octet-stream',
                ];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('CustomerMobileTicketController file upload error: ' . $e->getMessage());
            }
        }

        $reply = TicketReply::create([
            'ticket_id'      => $ticket->id,
            'message'        => $validated['message'] ?? '',
            'author_type'    => TicketReply::AUTHOR_CUSTOMER,
            'customer_name'  => $user->name ?? $user->mobile,
            'customer_mobile' => $user->mobile,
            'is_internal'    => false,
            'attachments'    => $attachments ?: null,
        ]);

        $this->bridge->syncReply(
            reply: $reply,
            ticket: $ticket,
            senderId: (string) $user->id,
            senderType: 'user',
            attachments: $attachments,
        );

        if ($ticket->status === Ticket::STATUS_OPEN) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        return response()->json([
            'data'    => $this->detail($ticket->fresh(['replies.user'])),
            'message' => 'پاسخ ثبت شد.',
        ]);
    }

    // ─── Private helpers ────────────────────────────────────────────────────

    private function findForUser(int $id, User $user): ?Ticket
    {
        $query = Ticket::withoutTenantIsolation()->whereNull('site_id');

        if (!($user->is_admin ?? false)) {
            $query->where(function ($q) use ($user) {
                $q->where('customer_id', $user->id);
                if ($user->mobile) {
                    $q->orWhere('customer_mobile', $user->mobile);
                }
            });
        }

        return $query
            ->with(['replies' => fn($q) => $q->where('is_internal', false)->orderBy('created_at')])
            ->find($id);
    }

    private function summary(Ticket $t): array
    {
        return [
            'id'              => $t->id,
            'ticket_number'   => $t->ticket_number,
            'subject'         => $t->subject,
            'status'          => $t->status,
            'priority'        => $t->priority,
            'category'        => $t->category,
            'customer_name'   => $t->customer_name,
            'customer_mobile' => $t->customer_mobile,
            'assignee_name'   => $t->assignee?->name,
            'created_at'      => $t->created_at?->toIso8601String(),
            'updated_at'      => $t->updated_at?->toIso8601String(),
        ];
    }

    private function detail(Ticket $t): array
    {
        return array_merge($this->summary($t), [
            'description' => $t->description,
            'tags'        => $t->tags,
            'attachments' => $t->attachments ?? [],
            'closed_at'   => $t->closed_at?->toIso8601String(),
            'replies'     => $t->relationLoaded('replies')
                ? $t->replies->map(fn(TicketReply $r) => [
                    'id'          => $r->id,
                    'message'     => $r->message,
                    'author_type' => $r->author_type,
                    'author_name' => $r->customer_name ?? ($r->author_type === 'staff' ? 'پشتیبان' : 'مشتری'),
                    'is_internal' => false,
                    'attachments' => $r->attachments ?? [],
                    'created_at'  => $r->created_at?->toIso8601String(),
                ])->values()
                : [],
        ]);
    }
}
