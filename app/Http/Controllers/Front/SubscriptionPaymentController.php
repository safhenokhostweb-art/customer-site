<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Models\SubscriptionPlan;
use App\Services\Payment\GatewayException;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionPaymentController extends Controller
{
    public function __construct(private SubscriptionService $service) {}

    /**
     * صفحه پرداخت — اگر درگاه پیکربندی شده، مستقیم ریدایرکت می‌کند.
     */
    public function pay(string $invoiceNumber)
    {
        if (! SiteHelper::isOwnerSite() && ! SiteHelper::moduleEnabled('subscription')) abort(404);

        $invoice = Invoice::where('invoice_number', $invoiceNumber)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return redirect()->route('account.subscriptions.index')
                ->with('success', 'این فاکتور قبلاً پرداخت شده است.');
        }

        // اگر درگاه پیکربندی شده → مستقیم به درگاه ریدایرکت کن
        if (PaymentGatewayFactory::isConfigured()) {
            return $this->redirectToGateway($invoice);
        }

        // درگاه پیکربندی نشده → نمایش صفحه پرداخت دستی
        return view('front.subscriptions.pay', compact('invoice'));
    }

    /**
     * Callback از درگاه بانکی — تأیید و فعال‌سازی.
     */
    public function callback(Request $request, string $invoiceNumber)
    {
        if (! SiteHelper::isOwnerSite() && ! SiteHelper::moduleEnabled('subscription')) abort(404);

        $invoice = Invoice::where('invoice_number', $invoiceNumber)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return redirect()->route('account.subscriptions.index')
                ->with('info', 'این فاکتور قبلاً پردازش شده است.');
        }

        try {
            $gateway = PaymentGatewayFactory::make();

            // اعتبارسنجی امضا / فرمت callback — قبل از verify
            $gateway->validateCallback(
                $request->all(),
                array_change_key_case($request->headers->all(), CASE_LOWER),
                $request->getContent()
            );

            $result = $gateway->verify($invoice, $request->all());
        } catch (GatewayException $e) {
            Log::error('Subscription callback gateway exception', [
                'invoice' => $invoiceNumber,
                'error'   => $e->getMessage(),
            ]);
            return redirect()->route('account.subscriptions.index')
                ->with('error', 'خطا در تأیید پرداخت: ' . $e->getMessage());
        }

        if (! $result->success) {
            return redirect()->route('account.subscriptions.index')
                ->with('error', 'پرداخت ناموفق بود: ' . $result->message);
        }

        return $this->activateSubscription($invoice, $result->refId, $result->cardPan);
    }

    /**
     * تأیید دستی — فقط وقتی درگاه پیکربندی نشده (Fallback).
     */
    public function confirm(Request $request, string $invoiceNumber)
    {
        if (! SiteHelper::isOwnerSite() && ! SiteHelper::moduleEnabled('subscription')) abort(404);

        // اگر درگاه پیکربندی شده، confirm دستی مجاز نیست
        if (PaymentGatewayFactory::isConfigured()) {
            return redirect()->route('subscriptions.pay', $invoiceNumber);
        }

        $invoice = Invoice::where('invoice_number', $invoiceNumber)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return redirect()->route('account.subscriptions.index')
                ->with('info', 'این فاکتور قبلاً پردازش شده است.');
        }

        return $this->activateSubscription($invoice, 'manual', '');
    }

    /**
     * فعال‌سازی اشتراک توسط ادمین.
     */
    public function activate(Request $request, Invoice $invoice)
    {
        abort_if(! auth()->user()?->is_admin, 403);

        $planId = $request->validate(['plan_id' => 'required|exists:subscription_plans,id'])['plan_id'];
        $plan   = SubscriptionPlan::findOrFail($planId);
        $user   = $invoice->user;

        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        $subscription = $this->service->subscribe($user, $plan, $invoice->id);

        return response()->json(['success' => true, 'subscription_id' => $subscription->id]);
    }

    // ─── Private ──────────────────────────────────────────────────────────

    private function redirectToGateway(Invoice $invoice)
    {
        $callbackUrl = $this->buildCallbackUrl($invoice->invoice_number);

        try {
            $gateway     = PaymentGatewayFactory::make();
            $redirectUrl = $gateway->request($invoice, auth()->user(), $callbackUrl);
        } catch (GatewayException $e) {
            Log::error('Subscription gateway request failed', [
                'invoice' => $invoice->invoice_number,
                'error'   => $e->getMessage(),
            ]);
            return redirect()->route('account.subscriptions.index')
                ->with('error', 'خطا در اتصال به درگاه پرداخت: ' . $e->getMessage());
        }

        return redirect()->away($redirectUrl);
    }

    private function activateSubscription(Invoice $invoice, string $refId, string $cardPan): \Illuminate\Http\RedirectResponse
    {
        $pendingSub = Subscription::where('invoice_id', $invoice->id)
            ->where('user_id', auth()->id())
            ->first();

        $planId = $pendingSub?->subscription_plan_id
            ?? session('pending_subscription_plan');

        if (! $planId) {
            return redirect()->route('account.subscriptions.plans')
                ->with('error', 'اطلاعات پلن یافت نشد. لطفاً دوباره پلن انتخاب کنید.');
        }

        $plan = SubscriptionPlan::find($planId);
        if (! $plan) {
            return redirect()->route('account.subscriptions.plans')
                ->with('error', 'پلن انتخابی یافت نشد.');
        }

        session()->forget('pending_subscription_plan');

        $user = auth()->user();

        $upgradeFromId    = $pendingSub?->upgrade_from_subscription_id;
        $creditAmount     = (float) ($pendingSub?->credit_amount ?? 0);

        DB::transaction(function () use ($invoice, $pendingSub, $plan, $user, $refId, $cardPan, $upgradeFromId) {
            $now = now();
            $invoice->update([
                'status'  => 'paid',
                'paid_at' => $now,
            ]);

            if ($pendingSub) {
                $action = $upgradeFromId
                    ? SubscriptionLog::ACTION_UPGRADED
                    : SubscriptionLog::ACTION_SUBSCRIBED;

                $pendingSub->update([
                    'status'    => Subscription::STATUS_ACTIVE,
                    'starts_at' => $now,
                    'ends_at'   => $now->copy()->addDays($plan->getDurationInDays()),
                ]);
                SubscriptionLog::create([
                    'subscription_id' => $pendingSub->id,
                    'user_id'         => $user->id,
                    'action'          => $action,
                    'performed_by'    => 'user',
                    'details'         => [
                        'plan'     => $plan->name,
                        'ref_id'   => $refId,
                        'card_pan' => $cardPan,
                        'gateway'  => config('payment.gateway', 'manual'),
                    ],
                ]);

                // اگر ارتقاء است، اشتراک قدیمی را cancel کن
                if ($upgradeFromId) {
                    $oldSub = Subscription::find($upgradeFromId);
                    if ($oldSub && $oldSub->user_id === $user->id && $oldSub->isActive()) {
                        $oldSub->update([
                            'status'              => Subscription::STATUS_CANCELLED,
                            'cancelled_at'        => $now,
                            'cancellation_reason' => 'تغییر پلن به «' . $plan->name . '»',
                        ]);
                        SubscriptionLog::create([
                            'subscription_id' => $oldSub->id,
                            'user_id'         => $user->id,
                            'action'          => SubscriptionLog::ACTION_CANCELLED,
                            'performed_by'    => 'system',
                            'details'         => ['reason' => 'plan_change'],
                        ]);
                        // کش benefit اعضای گروه قدیمی را flush کن
                        $this->service->invalidateGroupMemberCaches($oldSub);
                    }
                }

                $this->service->notifyActivation($user, $plan, $pendingSub->fresh());
            } else {
                $sub = $this->service->subscribe($user, $plan, $invoice->id);
            }
        });

        // اعتبار مازاد → امتیاز (خارج از transaction)
        if ($upgradeFromId && $creditAmount > 0 && $pendingSub) {
            $leftover       = max(0.0, $creditAmount - $plan->getEffectivePrice());
            $leftoverPoints = (int) floor($leftover / \App\Services\MembershipPointsService::EARN_PER_TOMAN);
            if ($leftoverPoints > 0) {
                try {
                    app(\App\Services\MembershipPointsService::class)
                        ->earnFromPlanChange($user, $pendingSub->fresh(), $leftoverPoints);
                } catch (\Throwable $e) {
                    Log::warning('Upgrade payment: leftover credit to points failed', ['error' => $e->getMessage()]);
                }
            }
        }

        // C02: پس از فعال‌سازی اشتراک، وضعیت lifecycle سایت را به active برگردان
        $this->triggerLifecycleReactivation();

        $successMsg = "اشتراک «{$plan->name}» با موفقیت فعال شد.";
        if ($refId && $refId !== 'manual') {
            $successMsg .= " شماره پیگیری: {$refId}";
        }

        return redirect()->route('account.subscriptions.index')->with('success', $successMsg);
    }

    /**
     * C02 — After subscription payment/activation, reactivate site lifecycle if suspended.
     * Calls platform API then updates local settings immediately for instant effect.
     * Failures are logged but never block the subscription activation.
     */
    private function triggerLifecycleReactivation(): void
    {
        try {
            $localStatus = \App\Models\Setting::get('site_lifecycle_status', 'active');

            // Only call platform if site might be non-active
            if (in_array($localStatus, ['suspended', 'grace_period', 'archived'], true)) {
                $platform = app(\App\Services\PlatformApiService::class);
                $ok = $platform->requestLifecycleReactivation();

                if ($ok) {
                    // Immediate local effect: clear suspended page for this request
                    \App\Models\Setting::set('site_lifecycle_status', 'active', 'lifecycle');
                    Log::info('C02: lifecycle reactivated after subscription payment');
                } else {
                    Log::warning('C02: platform lifecycle reactivation returned false', [
                        'local_status' => $localStatus,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('C02: lifecycle reactivation failed — non-blocking', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildCallbackUrl(string $invoiceNumber): string
    {
        $base = config('payment.callback_url');
        if (! empty($base)) {
            return rtrim($base, '/') . '/' . $invoiceNumber;
        }

        return route('subscriptions.callback', $invoiceNumber);
    }
}
