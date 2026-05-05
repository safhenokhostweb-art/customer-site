<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Jalali;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormSubmissionController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('forms') || !Schema::hasTable('form_submissions')) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'جدول فرم‌ها وجود ندارد. لطفاً دستور php artisan migrate را اجرا کنید.');
        }
        $query = FormSubmission::with(['form.fields'])->latest();

        if ($request->filled('form_id')) {
            $query->where('form_id', $request->form_id);
        }
        if ($request->filled('from')) {
            $fromCarbon = Jalali::toCarbon($request->from);
            if ($fromCarbon) {
                $query->whereDate('created_at', '>=', $fromCarbon->format('Y-m-d'));
            }
        }
        if ($request->filled('to')) {
            $toCarbon = Jalali::toCarbon($request->to);
            if ($toCarbon) {
                $query->whereDate('created_at', '<=', $toCarbon->format('Y-m-d'));
            }
        }
        if ($request->filled('q')) {
            $q = '%' . $request->q . '%';
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('form', fn ($b) => $b->where('title', 'like', $q))
                    ->orWhere('ip', 'like', $q);
            });
        }

        $submissions = $query->paginate(25)->withQueryString();

        $dataKeys = $this->collectDataKeys($submissions);
        $columnOrder = Session::get('submission_column_order', $this->defaultColumnOrder($dataKeys));
        $forms = Form::orderBy('title')->get(['id', 'title']);

        return view('admin.forms.submissions.index', compact('submissions', 'dataKeys', 'columnOrder', 'forms'));
    }

    public function updateColumnOrder(Request $request)
    {
        $order = $request->input('order', []);
        if (is_array($order)) {
            Session::put('submission_column_order', $order);
        }
        return response()->json(['ok' => true]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = FormSubmission::with(['form.fields'])->latest();

        if ($request->filled('form_id')) {
            $query->where('form_id', $request->form_id);
        }
        if ($request->filled('from')) {
            $fromCarbon = Jalali::toCarbon($request->from);
            if ($fromCarbon) {
                $query->whereDate('created_at', '>=', $fromCarbon->format('Y-m-d'));
            }
        }
        if ($request->filled('to')) {
            $toCarbon = Jalali::toCarbon($request->to);
            if ($toCarbon) {
                $query->whereDate('created_at', '<=', $toCarbon->format('Y-m-d'));
            }
        }
        if ($request->filled('q')) {
            $q = '%' . $request->q . '%';
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('form', fn ($b) => $b->where('title', 'like', $q))
                    ->orWhere('ip', 'like', $q);
            });
        }

        $submissions = $query->limit(10000)->get();
        $dataKeys = $this->collectDataKeysFromCollection($submissions);
        $columnOrder = Session::get('submission_column_order', $this->defaultColumnOrder($dataKeys));

        $filename = 'form-submissions-' . date('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($submissions, $columnOrder, $dataKeys) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            $headers = [];
            foreach ($columnOrder as $col) {
                if ($col === 'id') $headers[] = 'شناسه';
                elseif ($col === 'form') $headers[] = 'فرم';
                elseif ($col === 'date') $headers[] = 'تاریخ';
                elseif ($col === 'ip') $headers[] = 'IP';
                elseif (in_array($col, $dataKeys, true)) $headers[] = $col;
            }
            fputcsv($out, $headers);
            foreach ($submissions as $s) {
                $row = [];
                foreach ($columnOrder as $col) {
                    if ($col === 'id') $row[] = $s->id;
                    elseif ($col === 'form') $row[] = $s->form?->title ?? '';
                    elseif ($col === 'date') $row[] = $s->created_at ? Jalali::formatFa($s->created_at, 'Y/m/d H:i') : '';
                    elseif ($col === 'ip') $row[] = $s->ip ?? '';
                    elseif (in_array($col, $dataKeys, true)) $row[] = $s->data[$col] ?? '';
                }
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function collectDataKeys($submissions): array
    {
        $keys = [];
        foreach ($submissions as $s) {
            if (is_array($s->data)) {
                foreach (array_keys($s->data) as $k) {
                    $keys[$k] = true;
                }
            }
        }
        return array_keys($keys);
    }

    private function collectDataKeysFromCollection($submissions): array
    {
        $keys = [];
        foreach ($submissions as $s) {
            if (is_array($s->data)) {
                foreach (array_keys($s->data) as $k) {
                    $keys[$k] = true;
                }
            }
        }
        return array_keys($keys);
    }

    private function defaultColumnOrder(array $dataKeys): array
    {
        return array_merge(['id', 'form', 'date', 'ip'], $dataKeys);
    }
}
