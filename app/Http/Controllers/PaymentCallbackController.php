<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Passthrough برای callback بانک — وقتی callback روی دامنهٔ اصلی (سایت مالک) است
 * و بانک کاربر را به این آدرس برمی‌گرداند، این کنترلر آن را به پلتفرم فوروارد می‌کند.
 *
 * کاربرد: حفظ لینک بانک روی دامنهٔ اصلی (مثلاً safhenokhost.ir) در حالی که
 * checkout و verify در پلتفرم (panel.safhenokhost.ir) انجام می‌شود.
 */
class PaymentCallbackController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $platformUrl = rtrim(config('site.platform_url', ''), '/');
        if ($platformUrl === '') {
            abort(500, 'PLATFORM_URL تنظیم نشده است.');
        }

        $validator = Validator::make($request->query(), [
            'invoice' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'Authority' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'Status' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        if ($validator->fails()) {
            return redirect()->to($platformUrl . '/store/cart')->with('error', 'پارامتر بازگشت پرداخت نامعتبر است.');
        }

        $validated = $validator->validated();
        $invoice = $validated['invoice'];
        $authority = $validated['Authority'] ?? null;
        $status = $validated['Status'] ?? null;

        $targetUrl = $platformUrl . '/checkout/callback/' . $invoice;
        $query = http_build_query(array_filter([
            'Authority' => $authority,
            'Status' => $status,
        ]));

        return redirect()->away($query ? $targetUrl . '?' . $query : $targetUrl);
    }
}
