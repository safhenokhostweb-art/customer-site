<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Support\Facades\Http;

/**
 * فروشگاه ماژول داخل پنل ادمین tenant — ماژول‌های پولی با نام، توضیح، قیمت، دکمه خرید، وضعیت نصب.
 */
class ModuleStoreController extends Controller
{
    public function index()
    {
        $platformUrl = rtrim(config('site.platform_url', ''), '/');
        if ($platformUrl === '') {
            return redirect()->route('admin.dashboard')->with('warning', 'آدرس پلتفرم تنظیم نشده است.');
        }

        $license = License::current();
        $modules = [];
        $error = null;
        $platformStoreUrl = $platformUrl . '/store/modules';

        if ($license && $license->isValid()) {
            try {
            $response = Http::timeout(10)
                ->withToken($license->license_key)
                ->withHeaders(['X-Tenant-Domain' => request()->getHost()])
                ->get($platformUrl . '/api/tenant-marketplace/modules');
                if ($response->successful()) {
                    $data = $response->json();
                    if (! empty($data['allowed'])) {
                        $modules = $data['modules'] ?? [];
                        if (! empty($data['platform_store_url'])) {
                            $platformStoreUrl = $data['platform_store_url'];
                        }
                    } else {
                        $error = $data['error'] ?? 'دسترسی نامعتبر';
                    }
                } else {
                    $error = 'خطا در دریافت لیست ماژول‌ها از پلتفرم.';
                }
            } catch (\Throwable $e) {
                $error = 'اتصال به پلتفرم برقرار نشد.';
            }
        } else {
            $error = 'لایسنس معتبر وارد کنید تا لیست ماژول‌های قابل خرید نمایش داده شود.';
        }

        return view('admin.module-store.index', compact('modules', 'platformStoreUrl', 'error'));
    }
}
