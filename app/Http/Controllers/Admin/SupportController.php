<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplySupportRequest;
use App\Http\Requests\Admin\StoreSupportRequest;
use App\Models\License;
use App\Services\SupportApiClient;

/**
 * PHASE 7.25 — TASK 4: Tenant API Proxy Consumer (NOT a module owner).
 *
 * This controller does NOT own module business logic. It is a thin HTTP bridge:
 *   customer-site → SupportApiClient → saas-platform /api/tenant/tickets
 *
 * It does NOT:
 *   ✗ hold local ticket DB models
 *   ✗ duplicate ticket service logic
 *   ✗ own view authority (views are in _legacy/ pending package view migration)
 *
 * Migration path: PHASE 7.3 will replace local views with tickets:: package views
 * once tickets::site.* views are refactored to be platform-agnostic.
 *
 * @deprecated-views PHASE 7.15 — views at admin.support.* should be tickets::site.*
 */
class SupportController extends Controller
{
    public function __construct(
        protected SupportApiClient $supportApiClient
    ) {}

    public function create()
    {
        $license = License::current();

        return view('admin.support.create', compact('license'));
    }

    public function show(int|string $ticketId)
    {
        $license = License::current();
        $ticket = null;
        $error = null;

        if ($license && !empty($license->license_key) && !empty($license->domain)) {
            $result = $this->supportApiClient->find($license, $ticketId);
            $ticket = $result['ticket'];
            $error = $result['error'];
        } else {
            $error = 'لایسنس فعالی یافت نشد.';
        }

        return view('admin.support.show', compact('license', 'ticket', 'error'));
    }

    public function index()
    {
        [$license, $tickets, $error] = $this->loadTickets();

        return view('admin.support.index', compact('license', 'tickets', 'error'));
    }

    public function store(StoreSupportRequest $request)
    {
        $license = License::current();
        if (!$license || empty($license->license_key) || empty($license->domain)) {
            return back()->with('error', 'لایسنس فعالی یافت نشد. ابتدا لایسنس را در بخش لایسنس فعال کنید.');
        }

        $uploadedFiles = $this->supportApiClient->uploadAttachments($request->file('attachments', []));
        $validated = $request->validated();

        // تبدیل tags از string به array
        $tags = null;
        if (isset($validated['tags']) && is_string($validated['tags'])) {
            $tags = array_values(array_filter(
                array_map('trim', explode('،', $validated['tags'])),
                fn($tag) => $tag !== ''
            ));
        }

        $response = $this->supportApiClient->store($license, [
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'customer_email' => $validated['email'],
            'customer_name' => $validated['name'] ?? null,
            'priority' => $validated['priority'] ?? 'normal',
            'category' => $validated['category'] ?? null,
            'tags' => $tags,
        ], $uploadedFiles);

        if ($response['success'] ?? false) {
            return redirect()->route('admin.support.index')->with('success', 'تیکت شما با موفقیت ثبت شد. به‌زودی پاسخ داده می‌شود.');
        }

        return back()->with('error', $response['message'] ?? 'خطا در ثبت تیکت.')->withInput();
    }

    public function history()
    {
        [$license, $tickets, $error] = $this->loadTickets();

        return view('admin.support.history', compact('license', 'tickets', 'error'));
    }

    public function reply(ReplySupportRequest $request, int|string $ticketId)
    {
        $license = License::current();
        if (!$license || empty($license->license_key) || empty($license->domain)) {
            return back()->with('error', 'لایسنس فعالی یافت نشد.');
        }

        $uploadedFiles = $this->supportApiClient->uploadAttachments($request->file('attachments', []));
        $validated = $request->validated();

        $response = $this->supportApiClient->reply($license, $ticketId, [
            'message' => $validated['message'],
            'customer_name' => auth()->user()->name ?? 'مشتری',
        ], $uploadedFiles);

        if ($response['success'] ?? false) {
            return redirect()->route('admin.support.show', $ticketId)->with('success', 'پاسخ شما با موفقیت ثبت شد.');
        }

        return back()->with('error', $response['message'] ?? 'خطا در ثبت پاسخ.')->withInput();
    }

    protected function loadTickets(): array
    {
        $license = License::current();
        $tickets = collect();
        $error = null;

        if ($license && !empty($license->license_key) && !empty($license->domain)) {
            $result = $this->supportApiClient->index($license);
            $tickets = $result['tickets'];
            $error = $result['error'];
        } else {
            $error = 'لایسنس فعالی یافت نشد.';
        }

        return [$license, $tickets, $error];
    }
}
