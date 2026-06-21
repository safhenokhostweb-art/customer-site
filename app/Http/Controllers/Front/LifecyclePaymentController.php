<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\Payment\GatewayException;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\PlatformApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

/**
 * C01 — Restore Invoice Payment Flow
 *
 * Provides a direct payment page accessible even while site is suspended.
 * Flow: admin sends link → customer pays → site restored automatically.
 *
 * Route prefix: /lifecycle-pay/{invoiceRef}
 * These routes MUST be added to CheckSiteLifecycle ADMIN_PATHS whitelist.
 */
class LifecyclePaymentController extends Controller
{
    public function __construct(private PlatformApiService $platform) {}

    /**
     * Show the lifecycle invoice payment page.
     * GET /lifecycle-pay/{platformInvoiceRef}
     */
    public function show(string $invoiceRef)
    {
        $statusData = $this->platform->getLifecycleStatus();

        // Already active — no payment needed
        if (($statusData['is_active'] ?? false) === true) {
            return view('front.lifecycle.already-active');
        }

        $fee         = (int) ($statusData['restore_fee'] ?? 0);
        $breakdown   = $statusData['fee_breakdown'] ?? [];
        $siteStatus  = $statusData['status'] ?? 'suspended';

        return view('front.lifecycle.pay', [
            'invoiceRef'  => $invoiceRef,
            'fee'         => $fee,
            'breakdown'   => $breakdown,
            'siteStatus'  => $siteStatus,
            'isFree'      => $fee == 0,
        ]);
    }

    /**
     * Initiate payment or free restore.
     * POST /lifecycle-pay/{platformInvoiceRef}
     */
    public function initiate(Request $request, string $invoiceRef)
    {
        // Request restore from platform (creates/reuses SiteLifecycleInvoice)
        $restoreData = $this->platform->requestLifecycleRestore();

        if (! ($restoreData['success'] ?? false)) {
            // If already active just redirect home
            if (($restoreData['status'] ?? '') === 'active') {
                Setting::set('site_lifecycle_status', 'active', 'lifecycle');
                return redirect('/')->with('success', 'سایت شما فعال است.');
            }
            return back()->with('error', $restoreData['message'] ?? 'خطا در ارتباط با سرور.');
        }

        $amount         = (int) ($restoreData['amount'] ?? 0);
        $platformNumber = $restoreData['invoice_number'] ?? $invoiceRef;

        // Free restore — confirm immediately
        if ($amount == 0) {
            $ok = $this->platform->confirmLifecycleRestore($platformNumber, 'free_restore');
            if ($ok) {
                Setting::set('site_lifecycle_status', 'active', 'lifecycle');
                return redirect('/')->with('success', 'سایت شما با موفقیت فعال‌سازی مجدد شد.');
            }
            return back()->with('error', 'خطا در فعال‌سازی. با پشتیبانی تماس بگیرید.');
        }

        // Create local invoice for payment tracking
        $user    = auth()->user();
        $invoice = $this->createLocalInvoice($user, $amount, $platformNumber);

        session()->put('lifecycle_platform_invoice', $platformNumber);

        if (PaymentGatewayFactory::isConfigured()) {
            $callbackUrl = route('lifecycle.pay.callback', $invoice->invoice_number);
            try {
                $gateway     = PaymentGatewayFactory::make();
                $redirectUrl = $gateway->request($invoice, $user, $callbackUrl);
                return redirect()->away($redirectUrl);
            } catch (GatewayException $e) {
                Log::error('C01: lifecycle payment gateway request failed', ['error' => $e->getMessage()]);
                return back()->with('error', 'خطا در اتصال به درگاه پرداخت: ' . $e->getMessage());
            }
        }

        // No gateway — manual payment page
        return view('front.lifecycle.pay-manual', compact('invoice', 'amount'));
    }

    /**
     * Payment gateway callback.
     * GET /lifecycle-pay/callback/{localInvoiceNumber}
     */
    public function callback(Request $request, string $localInvoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $localInvoiceNumber)->firstOrFail();

        if ($invoice->status === 'paid') {
            Setting::set('site_lifecycle_status', 'active', 'lifecycle');
            return redirect('/')->with('success', 'سایت شما قبلاً فعال شده است.');
        }

        try {
            $gateway = PaymentGatewayFactory::make();
            $gateway->validateCallback(
                $request->all(),
                array_change_key_case($request->headers->all(), CASE_LOWER),
                $request->getContent()
            );
            $result = $gateway->verify($invoice, $request->all());
        } catch (GatewayException $e) {
            Log::error('C01: lifecycle payment callback exception', ['error' => $e->getMessage()]);
            return view('front.lifecycle.pay-failed', ['message' => $e->getMessage()]);
        }

        if (! $result->success) {
            return view('front.lifecycle.pay-failed', ['message' => $result->message]);
        }

        return $this->confirmAndRestore($invoice, $result->refId);
    }

    /**
     * Manual payment confirm (no gateway).
     */
    public function manualConfirm(Request $request, string $localInvoiceNumber)
    {
        if (PaymentGatewayFactory::isConfigured()) {
            return redirect('/lifecycle-pay');
        }

        $invoice = Invoice::where('invoice_number', $localInvoiceNumber)->firstOrFail();
        return $this->confirmAndRestore($invoice, 'manual');
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function confirmAndRestore(Invoice $invoice, string $refId): \Illuminate\Http\RedirectResponse
    {
        $platformNumber = session()->get('lifecycle_platform_invoice', 'UNKNOWN');

        DB::transaction(fn () => $invoice->update(['status' => 'paid', 'paid_at' => now()]));

        $ok = $this->platform->confirmLifecycleRestore($platformNumber, $refId);

        session()->forget('lifecycle_platform_invoice');

        if ($ok) {
            Setting::set('site_lifecycle_status', 'active', 'lifecycle');
            Log::info('C01: lifecycle restored after payment', [
                'invoice' => $invoice->invoice_number,
                'ref'     => $refId,
            ]);
            return redirect('/')->with('success', 'سایت با موفقیت فعال‌سازی مجدد شد. شماره پیگیری: ' . $refId);
        }

        Log::error('C01: confirmLifecycleRestore failed after verified payment', [
            'invoice'    => $invoice->invoice_number,
            'platform_n' => $platformNumber,
        ]);

        return redirect('/')->with('warning',
            'پرداخت دریافت شد. فعال‌سازی مجدد در حال پردازش است — با پشتیبانی تماس بگیرید. پیگیری: ' . $refId
        );
    }

    private function createLocalInvoice($user, int $amount, string $platformRef): Invoice
    {
        $number = 'LCPAY-' . now()->format('Ymd') . '-' . str_pad(
            Invoice::whereDate('created_at', today())->count() + 1,
            4, '0', STR_PAD_LEFT
        );

        $invoice = Invoice::create([
            'invoice_number'  => $number,
            'user_id'         => $user?->id,
            'customer_name'   => $user?->name ?? 'مشتری',
            'customer_mobile' => $user?->mobile,
            'subtotal'        => $amount,
            'discount'        => 0,
            'total'           => $amount,
            'status'          => 'pending',
            'notes'           => 'هزینه فعال‌سازی مجدد — platform_ref: ' . $platformRef,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title'      => 'فعال‌سازی مجدد سایت',
            'price'      => $amount,
            'quantity'   => 1,
            'total'      => $amount,
        ]);

        return $invoice;
    }
}
