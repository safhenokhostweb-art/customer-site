<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplySupportRequest;
use App\Http\Requests\Admin\StoreSupportRequest;
use App\Models\License;
use App\Services\SupportApiClient;

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

        $response = $this->supportApiClient->store($license, [
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'customer_email' => $validated['email'],
            'customer_name' => $validated['name'] ?? null,
            'priority' => $validated['priority'] ?? 'normal',
            'category' => $validated['category'] ?? null,
            'tags' => $validated['tags'] ?? null,
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
