<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * ارسال تیکت پشتیبانی به پلتفرم و نمایش تاریخچه تیکت‌ها.
 */
class SupportController extends Controller
{
    public function index()
    {
        $license = License::current();
        $tickets = collect();
        $error = null;

        if ($license && !empty($license->license_key) && !empty($license->domain)) {
            try {
                $platformUrl = rtrim(config('site.platform_url', ''), '/');
                if (!empty($platformUrl)) {
                    $response = Http::timeout(10)->get($platformUrl . '/api/support-tickets', [
                        'license_key' => $license->license_key,
                        'domain' => $license->domain,
                    ]);
                    $data = $response->json();
                    if (($data['success'] ?? false)) {
                        $tickets = collect($data['tickets'] ?? []);
                    }
                }
            } catch (\Throwable $e) {
                $error = 'خطا در دریافت تاریخچه تیکت‌ها';
            }
        }

        return view('admin.support.index', compact('license', 'tickets', 'error'));
    }

    public function store(Request $request)
    {
        $license = License::current();
        if (!$license || empty($license->license_key) || empty($license->domain)) {
            return back()->with('error', 'لایسنس فعالی یافت نشد. ابتدا لایسنس را در بخش لایسنس فعال کنید.');
        }

        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
        ]);

        $platformUrl = rtrim(config('site.platform_url', ''), '/');
        if (empty($platformUrl)) {
            return back()->with('error', 'PLATFORM_URL تنظیم نشده است.');
        }

        try {
            $response = Http::timeout(15)->post($platformUrl . '/api/support-tickets', [
                'license_key' => $license->license_key,
                'domain' => $license->domain,
                'subject' => $request->subject,
                'message' => $request->message,
                'customer_email' => $request->email,
                'customer_name' => $request->name,
            ]);

            $data = $response->json();
            if ($response->successful() && ($data['success'] ?? false)) {
                return redirect()->route('admin.support.index')->with('success', 'تیکت شما با موفقیت ثبت شد. به‌زودی پاسخ داده می‌شود.');
            }

            return back()->with('error', $data['message'] ?? 'خطا در ثبت تیکت.');
        } catch (\Throwable $e) {
            return back()->with('error', 'خطا در ارتباط با سرور: ' . $e->getMessage());
        }
    }
}
