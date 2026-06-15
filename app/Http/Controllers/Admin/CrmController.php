<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrmContact;
use App\Models\CrmDeal;
use App\Models\CrmActivity;
use App\Services\CrmService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class CrmController extends Controller
{
    public function __construct(private CrmService $crm) {}

    // ─── Dashboard ──────────────────────────────────────────────────────────

    public function index(): View
    {
        $stats = $this->crm->dashboardStats();
        return view('admin.crm.dashboard', $stats);
    }

    // ─── Contacts ───────────────────────────────────────────────────────────

    public function contacts(Request $request): View
    {
        $query = CrmContact::withCount(['deals', 'activities']);

        if ($q = $request->input('q')) {
            $query->where(fn ($b) =>
                $b->where('name', 'like', "%{$q}%")
                  ->orWhere('mobile', 'like', "%{$q}%")
                  ->orWhere('company', 'like', "%{$q}%")
            );
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        $contacts = $query->latest()->paginate(25)->withQueryString();

        return view('admin.crm.contacts.index', compact('contacts'));
    }

    public function showContact(CrmContact $contact): View
    {
        $contact->load(['deals', 'activities.deal', 'user']);
        return view('admin.crm.contacts.show', compact('contact'));
    }

    public function createContact(): View
    {
        return view('admin.crm.contacts.form', ['contact' => null]);
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:191',
            'mobile'   => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:191',
            'company'  => 'nullable|string|max:191',
            'position' => 'nullable|string|max:100',
            'source'   => 'required|in:manual,order,ticket,form,import',
            'status'   => 'required|in:lead,prospect,customer,churned',
            'city'     => 'nullable|string|max:100',
            'notes'    => 'nullable|string',
        ]);

        $contact = $this->crm->createContact($data);

        return redirect()->route('admin.crm.contacts.show', $contact)
            ->with('success', 'مخاطب ایجاد شد.');
    }

    public function editContact(CrmContact $contact): View
    {
        return view('admin.crm.contacts.form', compact('contact'));
    }

    public function updateContact(Request $request, CrmContact $contact): RedirectResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:191',
            'mobile'   => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:191',
            'company'  => 'nullable|string|max:191',
            'position' => 'nullable|string|max:100',
            'source'   => 'required|in:manual,order,ticket,form,import',
            'status'   => 'required|in:lead,prospect,customer,churned',
            'city'     => 'nullable|string|max:100',
            'notes'    => 'nullable|string',
        ]);

        $this->crm->updateContact($contact, $data);

        return redirect()->route('admin.crm.contacts.show', $contact)
            ->with('success', 'مخاطب بروزرسانی شد.');
    }

    public function destroyContact(CrmContact $contact): RedirectResponse
    {
        $contact->delete();
        return redirect()->route('admin.crm.contacts.index')
            ->with('success', 'مخاطب حذف شد.');
    }

    public function importContacts(): RedirectResponse
    {
        $count = $this->crm->importFromOrders();
        return redirect()->route('admin.crm.contacts.index')
            ->with('success', "تعداد {$count} مخاطب از سفارشات وارد شد.");
    }

    // ─── Activities ─────────────────────────────────────────────────────────

    public function storeActivity(Request $request, CrmContact $contact): RedirectResponse
    {
        $data = $request->validate([
            'type'         => 'required|in:call,email,meeting,note,task',
            'subject'      => 'required|string|max:255',
            'body'         => 'nullable|string',
            'outcome'      => 'nullable|in:none,interested,not_interested,follow_up',
            'scheduled_at' => 'nullable|date',
            'deal_id'      => 'nullable|exists:crm_deals,id',
        ]);

        $data['contact_id'] = $contact->id;
        $data['admin_id']   = auth()->id();
        $data['completed_at'] = now();

        $this->crm->logActivity($data);

        return back()->with('success', 'فعالیت ثبت شد.');
    }

    // ─── Deals ──────────────────────────────────────────────────────────────

    public function deals(): View
    {
        $pipeline = $this->crm->pipelineByStage();
        $stages   = CrmDeal::stages();
        return view('admin.crm.deals.pipeline', compact('pipeline', 'stages'));
    }

    public function storeDeal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contact_id'          => 'required|exists:crm_contacts,id',
            'title'               => 'required|string|max:191',
            'stage'               => 'required|in:lead,contacted,proposal,negotiation,won,lost',
            'value'               => 'nullable|integer|min:0',
            'probability'         => 'nullable|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'notes'               => 'nullable|string',
        ]);

        $deal = $this->crm->createDeal($data);

        return redirect()->route('admin.crm.contacts.show', $data['contact_id'])
            ->with('success', 'معامله ایجاد شد.');
    }

    public function moveDeal(Request $request, CrmDeal $deal): JsonResponse
    {
        $request->validate(['stage' => 'required|in:lead,contacted,proposal,negotiation,won,lost']);
        $this->crm->moveDeal($deal, $request->stage);
        return response()->json(['ok' => true]);
    }

    public function destroyDeal(CrmDeal $deal): RedirectResponse
    {
        $contactId = $deal->contact_id;
        $deal->delete();
        return redirect()->route('admin.crm.contacts.show', $contactId)
            ->with('success', 'معامله حذف شد.');
    }
}
