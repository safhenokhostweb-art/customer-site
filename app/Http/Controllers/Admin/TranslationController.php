<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentTranslation;
use App\Models\TranslationQueue;
use App\Services\ContentTranslationService;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    public function __construct(private ContentTranslationService $service)
    {
    }

    public function index()
    {
        $stats = [
            'done'       => ContentTranslation::count(),
            'pending'    => TranslationQueue::where('status', TranslationQueue::STATUS_PENDING)->count(),
            'failed'     => TranslationQueue::where('status', TranslationQueue::STATUS_FAILED)->count(),
            'processing' => TranslationQueue::where('status', TranslationQueue::STATUS_PROCESSING)->count(),
        ];

        $recentTranslations = ContentTranslation::latest()->take(20)->get();
        $failedItems = TranslationQueue::where('status', TranslationQueue::STATUS_FAILED)
            ->latest()->take(10)->get();

        return view('admin.translations.index', compact('stats', 'recentTranslations', 'failedItems'));
    }

    public function processBatch(Request $request)
    {
        $batch = (int) $request->input('batch', 20);
        $stats = $this->service->processQueue(min($batch, 50));

        return redirect()->route('admin.translations.index')
            ->with('success', "ترجمه انجام شد — پردازش‌شده: {$stats['processed']}، ناموفق: {$stats['failed']}");
    }

    public function retryFailed()
    {
        $count = TranslationQueue::where('status', TranslationQueue::STATUS_FAILED)
            ->update(['status' => TranslationQueue::STATUS_PENDING, 'attempts' => 0, 'last_error' => null]);

        return redirect()->route('admin.translations.index')
            ->with('success', "{$count} آیتم ناموفق برای تلاش مجدد بازنشانی شد.");
    }

    public function clearDone()
    {
        $count = TranslationQueue::where('status', TranslationQueue::STATUS_DONE)->delete();
        return redirect()->route('admin.translations.index')
            ->with('success', "{$count} آیتم تکمیل‌شده از صف حذف شد.");
    }

    public function stats()
    {
        return response()->json([
            'pending'    => TranslationQueue::where('status', TranslationQueue::STATUS_PENDING)->count(),
            'done'       => ContentTranslation::count(),
            'failed'     => TranslationQueue::where('status', TranslationQueue::STATUS_FAILED)->count(),
        ]);
    }
}
