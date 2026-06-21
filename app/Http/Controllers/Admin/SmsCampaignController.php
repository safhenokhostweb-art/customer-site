<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsCampaign;
use App\Services\SmsCampaignService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsCampaignController extends Controller
{
    public function __construct(
        private SmsCampaignService $campaignService,
        private SmsService         $smsService
    ) {}

    public function index(Request $request)
    {
        $siteId = app('site_id');
        $campaigns = SmsCampaign::where('site_id', $siteId)
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15);

        $stats = [
            'total'    => SmsCampaign::where('site_id', $siteId)->count(),
            'draft'    => SmsCampaign::where('site_id', $siteId)->where('status', 'draft')->count(),
            'running'  => SmsCampaign::where('site_id', $siteId)->where('status', 'running')->count(),
            'completed'=> SmsCampaign::where('site_id', $siteId)->where('status', 'completed')->count(),
        ];

        $smsConfigured = $this->smsService->isConfigured();

        return view('admin.sms.campaigns.index', compact('campaigns', 'stats', 'smsConfigured'));
    }

    public function create()
    {
        $smsConfigured = $this->smsService->isConfigured();
        return view('admin.sms.campaigns.create', compact('smsConfigured'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:200',
            'message'         => 'required|string|max:1000',
            'audience_type'   => 'required|in:all,crm,users,manual',
            'audience_filter' => 'nullable|array',
            'audience_filter.crm_status' => 'nullable|string',
            'audience_filter.mobiles'    => 'nullable|string',
            'scheduled_at'    => 'nullable|date|after:now',
        ]);

        $siteId = app('site_id');
        $campaign = $this->campaignService->create($data, $siteId, Auth::id());

        return redirect()
            ->route('admin.sms.campaigns.show', $campaign)
            ->with('success', 'کمپین پیامکی ایجاد شد.');
    }

    public function show(SmsCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);

        $stats      = $this->campaignService->getStats($campaign);
        $recipients = $campaign->recipients()->latest()->paginate(20);

        return view('admin.sms.campaigns.show', compact('campaign', 'stats', 'recipients'));
    }

    public function edit(SmsCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);
        abort_if(!$campaign->isDraft(), 403, 'فقط کمپین‌های پیش‌نویس قابل ویرایش هستند.');

        return view('admin.sms.campaigns.edit', compact('campaign'));
    }

    public function update(Request $request, SmsCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);
        abort_if(!$campaign->isDraft(), 403, 'فقط کمپین‌های پیش‌نویس قابل ویرایش هستند.');

        $data = $request->validate([
            'name'            => 'required|string|max:200',
            'message'         => 'required|string|max:1000',
            'audience_type'   => 'required|in:all,crm,users,manual',
            'audience_filter' => 'nullable|array',
            'audience_filter.crm_status' => 'nullable|string',
            'audience_filter.mobiles'    => 'nullable|string',
            'scheduled_at'    => 'nullable|date|after:now',
        ]);

        $this->campaignService->update($campaign, $data);

        return redirect()
            ->route('admin.sms.campaigns.show', $campaign)
            ->with('success', 'کمپین به‌روزرسانی شد.');
    }

    public function destroy(SmsCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);
        abort_if(!$campaign->isDraft(), 403, 'فقط کمپین‌های پیش‌نویس قابل حذف هستند.');

        $campaign->delete();

        return redirect()
            ->route('admin.sms.campaigns.index')
            ->with('success', 'کمپین حذف شد.');
    }

    public function send(SmsCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);
        abort_if($campaign->isCompleted() || $campaign->isRunning(), 403, 'این کمپین قابل ارسال مجدد نیست.');
        abort_unless($this->smsService->isConfigured(), 422, 'تنظیمات پیامک ناقص است.');

        // Build recipient list
        $count = $this->campaignService->buildRecipients($campaign);
        if ($count === 0) {
            return back()->with('error', 'مخاطبی یافت نشد.');
        }

        // Send (inline for small, queue for large)
        $this->campaignService->send($campaign);

        return redirect()
            ->route('admin.sms.campaigns.show', $campaign)
            ->with('success', "ارسال کمپین برای {$count} مخاطب شروع شد.");
    }

    public function cancel(SmsCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);
        abort_if($campaign->isCompleted(), 403, 'کمپین قابل لغو نیست.');

        $this->campaignService->cancel($campaign);

        return redirect()
            ->route('admin.sms.campaigns.show', $campaign)
            ->with('success', 'کمپین لغو شد.');
    }

    public function duplicate(SmsCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);

        $copy = $this->campaignService->duplicate($campaign, Auth::id());

        return redirect()
            ->route('admin.sms.campaigns.edit', $copy)
            ->with('success', 'کپی کمپین ایجاد شد.');
    }

    private function authorizeCampaign(SmsCampaign $campaign): void
    {
        abort_if($campaign->site_id !== app('site_id'), 404);
    }
}
