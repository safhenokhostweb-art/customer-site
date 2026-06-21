<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Setting;
use App\Services\Payment\GatewayException;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\PlatformApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * C04 — Customer Self-Service Lifecycle Reactivation.
 *
 * Flow:
 *   1. suspended.blade.php → POST /account/lifecycle/restore → requestRestore()
 *   2. If fee=0: direct reactivation via platform API
 *   3. If fee>0: create local invoice → redirect to payment gateway
 *   4. callback() → verify payment → confirmRestore() on platform → site active
 */
class AccountLifecycleController extends Controller
{
    public function __construct(private PlatformApiService $platform) {}

    /**
     * C04 Step 1: Customer initiates restore.
     * If fee = 0: activate immediately.
     * If fee > 0: create invoice and redirect to payment gateway.
     */
    public function requestRestore(Request $request)
    {
        $restoreData = $this->platform->requestLifecycleRestore();

        if (! ($restoreData['success'] ?? false)) {
            $error = $restoreData['message'] ?? 'خطا در ارتباط با پلتفرم.';

            // If site already active, redirect to dashboard
            if (($restoreData['status'] ?? '') === 'active') {
                Setting::set('site_lifecycle_status', 'active', 'lifecycle');
                return redirect('/')->with('success', 'سایت شما فعال است.');
            }

            return back()->with('error', $error);
        }

        $amount        = (int) ($restoreData['amount'] ?? 0);
        $invoiceNumber = $restoreData['invoice_number'] ?? null;

        // Free restore — reactivate immediately
        if ($amount == 0 || ($restoreData['is_free'] ?? false)) {
            $ok = $this->platform->confirmLifecycleRestore(
                $invoiceNumber ?? 'FREE-' . now()->format('YmdHis'),
                'free_restore'
            );

            if ($ok) {
                Setting::set('site_lifecycle_status', 'active', 'lifecycle');
                return redirect('/')->with('success', 'سایت شما با موفقیت فعال‌سازی مجدد شد.');
            }

            return back()->with('error', 'خطا در فعال‌سازی مجدد. با پشتیبانی تماس بگیرید.');
        }

        // Paid restore — create local invoice and redirect to gateway
        $user    = auth()->user();
        $invoice = $this->createRestoreInvoice($user, $amount, $invoiceNumber);

        Session::put('lifecycle_restore_invoice_number', $invoiceNumber);
        Session::put('lifecycle_restore_local_invoice', $invoice->invoice_number);

        if (PaymentGatewayFactory::isConfigured()) {
            return $this->redirectToGateway($invoice);
        }

        // No gateway configured — show manual payment page
        return view('front.lifecycle.restore-pay', compact('invoice', 'amount', 'restoreData'));
    }

    /**
     * C04 Step 2: Payment gateway callback — verify and confirm restore.
     */
    public function callback(Request $request, string $invoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)
            ->where('user_id', auth()->id())
            ->firstOrFail();

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
            Log::error('C04: lifecycle restore payment gateway exception', [
                'invoice' => $invoiceNumber,
                'error'   => $e->getMessage(),
            ]);
            return redirect()->route('account.dashboard')
                ->with('error', 'خطا در تأیید پرداخت: ' . $e->getMessage());
        }

        if (! $result->success) {
            return redirect()->route('account.dashboard')
                ->with('error', 'پرداخت ناموفق: ' . $result->message);
        }

        return $this->finalizeRestore($invoice, $result->refId);
    }

    /**
     * Manual payment confirm (no gateway configured).
     */
    public function manualConfirm(Request $request, string $invoiceNumber)
    {
        if (PaymentGatewayFactory::isConfigured()) {
            return redirect()->route('account.lifecycle.restore');
        }

        $invoice = Invoice::where('invoice_number', $invoiceNumber)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            Setting::set('site_lifecycle_status', 'active', 'lifecycle');
            return redirect('/')->with('success', 'سایت شما فعال شده است.');
        }

        return $this->finalizeRestore($invoice, 'manual');
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function finalizeRestore(Invoice $invoice, string $refId): \Illuminate\Http\RedirectResponse
    {
        $platformInvoiceNumber = Session::get('lifecycle_restore_invoice_number');

        DB::transaction(function () use ($invoice, $refId) {
            $invoice->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);
        });

        $ok = $this->platform->confirmLifecycleRestore(
            $platformInvoiceNumber ?? 'UNKNOWN',
            $refId
        );

        if ($ok) {
            Setting::set('site_lifecycle_status', 'active', 'lifecycle');
            Session::forget(['lifecycle_restore_invoice_number', 'lifecycle_restore_local_invoice']);

            Log::info('C04: lifecycle restored after payment', [
                'invoice' => $invoice->invoice_number,
                'ref'     => $refId,
            ]);

            return redirect('/')->with('success', 'سایت شما با موفقیت فعال‌سازی مجدد شد. شماره پیگیری: ' . $refId);
        }

        Log::error('C04: confirmLifecycleRestore failed after payment', [
            'invoice' => $invoice->invoice_number,
        ]);

        return redirect()->route('account.dashboard')
            ->with('warning', 'پرداخت دریافت شد اما فعال‌سازی مجدد با تأخیر انجام می‌شود. با پشتیبانی تماس بگیرید.');
    }

    private function createRestoreInvoice($user, int $amount, ?string $platformInvoiceRef): Invoice
    {
        $number = 'RESTORE-' . now()->format('Ymd') . '-' . str_pad(
            Invoice::whereDate('created_at', today())->count() + 1,
            4, '0', STR_PAD_LEFT
        );

        $invoice = Invoice::create([
            'invoice_number'  => $number,
            'user_id'         => $user->id,
            'customer_name'   => $user->name ?? $user->mobile,
            'customer_mobile' => $user->mobile,
            'subtotal'        => $amount,
            'discount'        => 0,
            'total'           => $amount,
            'status'          => 'pending',
            'notes'           => 'هزینه فعال‌سازی مجدد سایت — ref: ' . ($platformInvoiceRef ?? '-'),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title'      => 'هزینه فعال‌سازی مجدد سایت',
            'price'      => $amount,
            'quantity'   => 1,
            'total'      => $amount,
        ]);

        return $invoice;
    }

    private function redirectToGateway(Invoice $invoice)
    {
        $callbackUrl = route('account.lifecycle.callback', $invoice->invoice_number);

        try {
            $gateway     = PaymentGatewayFactory::make();
            $redirectUrl = $gateway->request($invoice, auth()->user(), $callbackUrl);
            return redirect()->away($redirectUrl);
        } catch (GatewayException $e) {
            Log::error('C04: lifecycle restore gateway request failed', [
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'خطا در اتصال به درگاه پرداخت: ' . $e->getMessage());
        }
    }
}
