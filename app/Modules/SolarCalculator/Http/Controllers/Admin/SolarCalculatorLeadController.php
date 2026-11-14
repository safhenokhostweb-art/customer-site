<?php

namespace App\Modules\SolarCalculator\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\SolarCalculator\Http\Controllers\SolarCalculatorController;
use App\Modules\SolarCalculator\Models\SolarCalculatorLead;
use App\Modules\SolarCalculator\Services\SolarCalculatorSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SolarCalculatorLeadController extends Controller
{
    public function __construct(private readonly SolarCalculatorSettings $settings)
    {
    }

    public function index(Request $request): View
    {
        $leads = $this->filtered($request)->latest()->paginate(25)->withQueryString();

        $counts = SolarCalculatorLead::query()
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->all();

        return view('modules.solar-calculator.admin.leads.index', [
            'leads' => $leads,
            'counts' => $counts,
            'total' => array_sum($counts),
            'statuses' => SolarCalculatorLead::STATUSES,
            'regions' => collect($this->settings->regions())->pluck('label', 'key')->all(),
        ]);
    }

    public function show(SolarCalculatorLead $lead): View
    {
        return view('modules.solar-calculator.admin.leads.show', [
            'lead' => $lead->load('crmContact', 'user'),
            'statuses' => SolarCalculatorLead::STATUSES,
            'regions' => collect($this->settings->regions())->pluck('label', 'key')->all(),
        ]);
    }

    public function update(Request $request, SolarCalculatorLead $lead): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(SolarCalculatorLead::STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);
        $lead->update($data);

        return back()->with('success', 'وضعیت درخواست به‌روزرسانی شد.');
    }

    public function destroy(SolarCalculatorLead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('admin.solar-calculator.leads.index')->with('success', 'درخواست حذف شد.');
    }

    public function quote(SolarCalculatorLead $lead, SolarCalculatorController $public): Response
    {
        return $public->quoteResponse($lead, false, true);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)->latest();
        $statuses = SolarCalculatorLead::STATUSES;

        return response()->streamDownload(function () use ($query, $statuses): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel opens UTF-8 Persian correctly with a BOM.
            fputcsv($out, ['شماره', 'تاریخ', 'نام', 'موبایل', 'ایمیل', 'نوع سیستم', 'استان', 'ظرفیت (kW)', 'مبلغ (تومان)', 'وضعیت']);
            $query->chunk(500, function ($rows) use ($out, $statuses): void {
                foreach ($rows as $lead) {
                    fputcsv($out, [
                        $lead->quote_number,
                        $lead->created_at?->format('Y-m-d H:i'),
                        self::csvSafe($lead->name),
                        $lead->mobile,
                        self::csvSafe((string) $lead->email),
                        $lead->system_type,
                        $lead->region_key,
                        $lead->results['values']['system_kw'] ?? '',
                        $lead->total_price !== null ? (int) $lead->total_price : '',
                        $statuses[$lead->status] ?? $lead->status,
                    ]);
                }
            });
            fclose($out);
        }, 'solar-leads-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Stops spreadsheet formula injection from visitor-typed text. */
    private static function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    private function filtered(Request $request): Builder
    {
        $query = SolarCalculatorLead::query();
        if (($status = (string) $request->query('status', '')) !== '' && array_key_exists($status, SolarCalculatorLead::STATUSES)) {
            $query->where('status', $status);
        }
        if (($type = (string) $request->query('system_type', '')) !== '') {
            $query->where('system_type', $type);
        }
        if (($q = trim((string) $request->query('q', ''))) !== '') {
            $q = mb_substr($q, 0, 100);
            $query->where(function (Builder $w) use ($q): void {
                $w->where('name', 'like', '%' . addcslashes($q, '%_\\') . '%')
                    ->orWhere('mobile', 'like', '%' . addcslashes($q, '%_\\') . '%')
                    ->orWhere('quote_number', $q);
            });
        }

        return $query;
    }
}
