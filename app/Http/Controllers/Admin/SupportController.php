<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplySupportRequest;
use App\Http\Requests\Admin\StoreSupportRequest;
use App\Models\License;
use App\Models\User;
use App\Services\SupportApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SaaSPlatform\Modules\Tickets\Dto\TicketProxyData;

/**
 * Phase 9.1 — Canonical Frontend Consumer.
 *
 * This controller is a thin HTTP bridge:
 *   customer-site → SupportApiClient → saas-platform /api/tenant/tickets
 *
 * Frontend Authority: ALL views rendered here use tickets::site.tickets.* ONLY.
 * No local ticket views are rendered by this controller.
 *
 * Data adaptation: API array responses are wrapped in TicketProxyData DTOs
 * so that canonical Blade views can use object-style property access.
 */
class SupportController extends Controller
{
    public function __construct(
        protected SupportApiClient $supportApiClient
    ) {}

    public function index()
    {
        [$license, $rawTickets, $error] = $this->loadTickets();

        $tickets = TicketProxyData::collectionFromApiResult($rawTickets);
        $stats   = TicketProxyData::statsFrom($tickets);

        // Agent workload — active tickets grouped by assignee
        $agentWorkload = $tickets
            ->filter(fn($t) => in_array($t->status, ['open', 'in_progress']) && $t->assignee !== null)
            ->groupBy(fn($t) => $t->assignee->name)
            ->map(fn($group, $name) => ['name' => $name, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();

        $totalActive = max(
            $tickets->whereIn('status', ['open', 'in_progress'])->count(),
            1
        );

        // Priority × status heatmap (open + in_progress columns only)
        $heatmap = collect([
            ['key' => 'high',   'label' => 'بالا'],
            ['key' => 'normal', 'label' => 'عادی'],
            ['key' => 'low',    'label' => 'کم'],
        ])->map(fn($row) => array_merge($row, [
            'open'        => $tickets->where('priority', $row['key'])->where('status', 'open')->count(),
            'in_progress' => $tickets->where('priority', $row['key'])->where('status', 'in_progress')->count(),
            'closed'      => $tickets->where('priority', $row['key'])->where('status', 'closed')->count(),
        ]))->toArray();

        return view('tickets::site.tickets.admin_index', compact(
            'tickets', 'stats', 'error', 'agentWorkload', 'totalActive', 'heatmap'
        ));
    }

    public function create()
    {
        $license = License::current();
        $users   = User::orderBy('name')->get(['id', 'name', 'mobile']);

        return view('tickets::site.tickets.create', [
            'license'            => $license,
            'users'              => $users,
            'attachmentsEnabled' => true,
            // Section K: Customer Site Admin can submit Feature Request tickets to SaaS platform
            'isSiteAdminContext' => true,
        ]);
    }

    public function show(int|string $ticketId)
    {
        $license = License::current();
        $ticket  = null;
        $error   = null;

        if ($license && !empty($license->license_key) && !empty($license->domain)) {
            $result     = $this->supportApiClient->find($license, $ticketId);
            $rawTicket  = $result['ticket'];
            $error      = $result['error'];

            if ($rawTicket) {
                $ticket = TicketProxyData::fromArray(is_array($rawTicket) ? $rawTicket : (array) $rawTicket);
            }
        } else {
            $error = 'لایسنس فعالی یافت نشد.';
        }

        if (!$ticket) {
            abort(404);
        }

        return view('tickets::site.tickets.show', [
            'ticket'             => $ticket,
            'error'              => $error,
            'activities'         => collect(),
            'attachmentsEnabled' => true,
        ]);
    }

    public function store(StoreSupportRequest $request)
    {
        $license = License::current();
        if (!$license || empty($license->license_key) || empty($license->domain)) {
            return back()->with('error', 'لایسنس فعالی یافت نشد. ابتدا لایسنس را در بخش لایسنس فعال کنید.');
        }

        $uploadedFiles = $this->supportApiClient->uploadAttachments($request->file('attachments', []));
        $validated     = $request->validated();

        $tags = null;
        if (isset($validated['tags']) && is_string($validated['tags'])) {
            $tags = array_values(array_filter(
                array_map('trim', explode('،', $validated['tags'])),
                fn($tag) => $tag !== ''
            ));
        }

        $forUserId = $request->input('for_user_id');
        if ($forUserId) {
            $targetUser = User::find($forUserId);
            if ($targetUser) {
                $validated['mobile'] = $targetUser->mobile;
                $validated['name']   = $targetUser->name;
            }
        }

        $ticketType = $validated['type'] ?? 'support';

        // برای درخواست ویژگی، اگر موبایل ارائه نشد از موبایل ادمین سایت استفاده کن
        $customerMobile = $validated['mobile'] ?? null;
        $customerName   = $validated['name'] ?? null;
        if ($ticketType === 'feature_request' && empty($customerMobile)) {
            $customerMobile = auth()->user()?->mobile;
            $customerName   = $customerName ?: auth()->user()?->name;
        }

        $response = $this->supportApiClient->store($license, [
            'subject'         => $validated['subject'],
            'message'         => $validated['message'] ?? $validated['description'] ?? '',
            'customer_mobile' => $customerMobile,
            'customer_name'   => $customerName,
            'priority'        => $validated['priority'] ?? 'normal',
            'category'        => $validated['category'] ?? null,
            'type'            => $ticketType,
            'tags'            => $tags,
        ], $uploadedFiles);

        if ($response['success'] ?? false) {
            return redirect()->route('tickets.site.index')
                ->with('success', 'تیکت شما با موفقیت ثبت شد. به‌زودی پاسخ داده می‌شود.');
        }

        return back()->with('error', $response['message'] ?? 'خطا در ثبت تیکت.')->withInput();
    }

    public function chatSettings()
    {
        return view('tickets::site.tickets.settings');
    }

    public function history()
    {
        [$license, $rawTickets, $error] = $this->loadTickets();

        $tickets = TicketProxyData::collectionFromApiResult($rawTickets);

        return view('tickets::site.tickets.history', [
            'tickets' => $tickets,
            'error'   => $error,
        ]);
    }

    public function reply(ReplySupportRequest $request, int|string $ticketId)
    {
        $license = License::current();
        if (!$license || empty($license->license_key) || empty($license->domain)) {
            return back()->with('error', 'لایسنس فعالی یافت نشد.');
        }

        $uploadedFiles = $this->supportApiClient->uploadAttachments($request->file('attachments', []));
        $validated     = $request->validated();

        $response = $this->supportApiClient->reply($license, $ticketId, [
            'message'       => $validated['message'],
            'customer_name' => auth()->user()?->name ?? 'مشتری',
        ], $uploadedFiles);

        if ($response['success'] ?? false) {
            if ($request->ajax() || $request->wantsJson()) {
                $replies   = $response['ticket']['replies'] ?? [];
                $lastReply = !empty($replies) ? end($replies) : null;
                return response()->json(['success' => true, 'reply' => $lastReply ?: null]);
            }
            return redirect()->route('tickets.site.show', $ticketId)
                ->with('success', 'پاسخ شما با موفقیت ثبت شد.');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $response['message'] ?? 'خطا در ثبت پاسخ.'], 422);
        }

        return back()->with('error', $response['message'] ?? 'خطا در ثبت پاسخ.')->withInput();
    }

    public function poll(Request $request, int|string $ticketId): JsonResponse
    {
        $license = License::current();
        if (! $license || empty($license->license_key) || empty($license->domain)) {
            return response()->json(['replies' => [], 'last_id' => 0]);
        }

        $result = $this->supportApiClient->poll($license, $ticketId, (int) $request->query('last_id', 0));

        return response()->json([
            'replies' => $result['replies'],
            'last_id' => $result['last_id'],
        ]);
    }

    protected function loadTickets(): array
    {
        $license = License::current();
        $tickets = collect();
        $error   = null;

        if ($license && !empty($license->license_key) && !empty($license->domain)) {
            $result  = $this->supportApiClient->index($license);
            $tickets = $result['tickets'];
            $error   = $result['error'];
        } else {
            $error = 'لایسنس فعالی یافت نشد.';
        }

        return [$license, $tickets, $error];
    }

    public function downloadAttachment(string $path)
    {
        $path = ltrim($path, '/');

        if (str_contains($path, '..') || ! str_starts_with($path, 'ticket-attachments/')) {
            abort(403);
        }

        if (! \Illuminate\Support\Facades\Storage::disk('private')->exists($path)) {
            abort(404);
        }

        return \Illuminate\Support\Facades\Storage::disk('private')->response($path);
    }
}
