<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * مدیریت تیکت‌های پشتیبانی در سایت مشتری.
 * تیکت‌ها از طریق API به پلتفرم ارسال و دریافت می‌شوند.
 */
class SupportController extends Controller
{
    /**
     * نمایش صفحه ایجاد تیکت جدید.
     */
    public function create()
    {
        $license = License::current();
        return view("admin.support.create", compact("license"));
    }

    /**
     * نمایش جزئیات یک تیکت خاص.
     */
    public function show($ticketId)
    {
        $license = License::current();
        $ticket = null;
        $error = null;

        if ($license && !empty($license->license_key) && !empty($license->domain)) {
            try {
                $platformUrl = rtrim(config("site.platform_url", ""), "/");
                if (!empty($platformUrl)) {
                    $response = Http::timeout(10)->get($platformUrl . "/api/support-tickets", [
                        "license_key" => $license->license_key,
                        "domain" => $license->domain,
                    ]);
                    $data = $response->json();
                    if (($data["success"] ?? false)) {
                        $tickets = collect($data["tickets"] ?? []);
                        $ticket = $tickets->firstWhere('id', $ticketId);
                    }
                }
            } catch (\Throwable $e) {
                $error = "خطا در دریافت اطلاعات تیکت";
            }
        }

        if (!$ticket && !$error) {
            $error = "تیکت مورد نظر یافت نشد.";
        }

        return view("admin.support.show", compact("license", "ticket", "error"));
    }

    /**
     * نمایش مرکز پشتیبانی با لیست تیکت‌ها و فرم ثبت تیکت جدید.
     */
    public function index()
    {
        $license = License::current();
        $tickets = collect();
        $error = null;

        if ($license && !empty($license->license_key) && !empty($license->domain)) {
            try {
                $platformUrl = rtrim(config("site.platform_url", ""), "/");
                if (!empty($platformUrl)) {
                    $response = Http::timeout(10)->get($platformUrl . "/api/support-tickets", [
                        "license_key" => $license->license_key,
                        "domain" => $license->domain,
                    ]);
                    $data = $response->json();
                    if (($data["success"] ?? false)) {
                        $tickets = collect($data["tickets"] ?? []);
                    }
                }
            } catch (\Throwable $e) {
                $error = "خطا در دریافت تاریخچه تیکت‌ها";
            }
        }

        return view("admin.support.index", compact("license", "tickets", "error"));
    }

    /**
     * ذخیره تیکت جدید (ارسال به پلتفرم).
     */
    public function store(Request $request)
    {
        $license = License::current();
        if (!$license || empty($license->license_key) || empty($license->domain)) {
            return back()->with("error", "لایسنس فعالی یافت نشد. ابتدا لایسنس را در بخش لایسنس فعال کنید.");
        }

        $request->validate([
            "subject" => "required|string|max:255",
            "message" => "required|string|max:5000",
            "email" => "required|email",
            "name" => "nullable|string|max:255",
            "priority" => "nullable|in:low,normal,high",
            "category" => "nullable|string|max:50",
            "tags" => "nullable|string|max:500",
            "attachments.*" => "nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,zip|max:10240",
        ]);

        $platformUrl = rtrim(config("site.platform_url", ""), "/");
        if (empty($platformUrl)) {
            return back()->with("error", "PLATFORM_URL تنظیم نشده است.");
        }

        // آپلود فایل‌های ضمیمه (محلی در customer-site)
        $uploadedFiles = [];
        if ($request->hasFile("attachments")) {
            $allFiles = $request->allFiles();
            $files = $allFiles["attachments"] ?? [];
            $files = is_array($files) ? $files : [$files];
            foreach ($files as $file) {
                try {
                    if (!$file || !$file->isValid()) continue;
                    $path = $file->store("support-tickets/" . now()->format("Y/m"), "public");
                    if (empty($path)) continue;
                    $uploadedFiles[] = [
                        "name" => $file->getClientOriginalName(),
                        "path" => $path,
                        "size" => $file->getSize(),
                        "mime" => $file->getMimeType(),
                    ];
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        try {
            $payload = [
                "license_key" => $license->license_key,
                "domain" => $license->domain,
                "subject" => $request->subject,
                "message" => $request->message,
                "customer_email" => $request->email,
                "customer_name" => $request->name,
                "priority" => $request->input("priority", "normal"),
                "category" => $request->category,
                "tags" => $request->tags,
                "attachments" => $uploadedFiles,
            ];

            $response = Http::timeout(15)->post($platformUrl . "/api/support-tickets", $payload);

            $data = $response->json();
            if ($response->successful() && ($data["success"] ?? false)) {
                return redirect()->route("admin.support.index")->with("success", "تیکت شما با موفقیت ثبت شد. به‌زودی پاسخ داده می‌شود.");
            }

            // حذف فایل‌های آپلود شده در صورت خطا
            foreach ($uploadedFiles as $f) {
                Storage::disk("public")->delete($f["path"]);
            }

            return back()->with("error", $data["message"] ?? "خطا در ثبت تیکت.");
        } catch (\Throwable $e) {
            foreach ($uploadedFiles as $f) {
                Storage::disk("public")->delete($f["path"]);
            }
            return back()->with("error", "خطا در ارتباط با سرور: " . $e->getMessage());
        }
    }

    /**
     * نمایش تاریخچه تیکت‌ها (صفحه مجزا)
     */
    public function history()
    {
        $license = License::current();
        $tickets = collect();
        $error = null;

        if ($license && !empty($license->license_key) && !empty($license->domain)) {
            try {
                $platformUrl = rtrim(config("site.platform_url", ""), "/");
                if (!empty($platformUrl)) {
                    $response = Http::timeout(10)->get($platformUrl . "/api/support-tickets", [
                        "license_key" => $license->license_key,
                        "domain" => $license->domain,
                    ]);
                    $data = $response->json();
                    if (($data["success"] ?? false)) {
                        $tickets = collect($data["tickets"] ?? []);
                    }
                }
            } catch (\Throwable $e) {
                $error = "خطا در دریافت تاریخچه تیکت‌ها";
            }
        }

        return view("admin.support.history", compact("license", "tickets", "error"));
    }

    /**
     * ارسال پاسخ به یک تیکت خاص
     */
    public function reply(Request $request, $ticketId)
    {
        $license = License::current();
        if (!$license || empty($license->license_key) || empty($license->domain)) {
            return back()->with("error", "لایسنس فعالی یافت نشد.");
        }

        $request->validate([
            "message" => "required|string|max:5000",
        ]);

        $platformUrl = rtrim(config("site.platform_url", ""), "/");
        if (empty($platformUrl)) {
            return back()->with("error", "PLATFORM_URL تنظیم نشده است.");
        }

        try {
            $response = Http::timeout(15)->post($platformUrl . "/api/support-tickets/" . $ticketId . "/reply", [
                "license_key" => $license->license_key,
                "domain" => $license->domain,
                "message" => $request->message,
                "customer_name" => auth()->user()->name ?? "مشتری",
            ]);

            $data = $response->json();
            if ($response->successful() && ($data["success"] ?? false)) {
                return redirect()->route("admin.support.history")->with("success", "پاسخ شما با موفقیت ثبت شد.");
            }

            return back()->with("error", $data["message"] ?? "خطا در ثبت پاسخ.");
        } catch (\Throwable $e) {
            return back()->with("error", "خطا در ارتباط با سرور: " . $e->getMessage());
        }
    }
}
