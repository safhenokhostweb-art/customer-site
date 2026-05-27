<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplySupportRequest;
use App\Http\Requests\Admin\StoreSupportRequest;
use App\Models\License;
use App\Models\User;
use App\Services\SupportApiClient;
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

        return view('tickets::site.tickets.index', [
            'tickets' => $tickets,
            'stats'   => $stats,
            'error'   => $error,
        ]);
    }

    public function create()
    {
        $license = License::current();
        $users   = User::orderBy('name')->get(['id', 'name', 'email', 'mobile']);

        return view('tickets::site.tickets.create', [
            'license'            => $license,
            'users'              => $users,
            'attachmentsEnabled' => false,
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
            'ticket' => $ticket,
            'error'  => $error,
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
                $validated['email'] = $targetUser->email;
                $validated['name']  = $targetUser->name;
            }
        }

        $response = $this->supportApiClient->store($license, [
            'subject'        => $validated['subject'],
            'message'        => $validated['message'] ?? $validated['description'] ?? '',
            'customer_email' => $validated['email'] ?? null,
            'customer_name'  => $validated['name'] ?? null,
            'priority'       => $validated['priority'] ?? 'normal',
            'category'       => $validated['category'] ?? null,
            'tags'           => $tags,
        ], $uploadedFiles);

        if ($response['success'] ?? false) {
            return redirect()->route('tickets.site.index')
                ->with('success', 'تیکت شما با موفقیت ثبت شد. به‌زودی پاسخ داده می‌شود.');
        }

        return back()->with('error', $response['message'] ?? 'خطا در ثبت تیکت.')->withInput();
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
            return redirect()->route('tickets.site.show', $ticketId)
                ->with('success', 'پاسخ شما با موفقیت ثبت شد.');
        }

        return back()->with('error', $response['message'] ?? 'خطا در ثبت پاسخ.')->withInput();
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
}
