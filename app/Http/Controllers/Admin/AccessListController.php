<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessGate;
use App\Models\AccessList;
use App\Services\AccessListImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccessListController extends Controller
{
    public function __construct(private AccessListImportService $importer) {}

    // ── Lists ──────────────────────────────────────────────────────────────

    public function listsIndex()
    {
        $lists = AccessList::withCount('entries')->latest()->get();
        return view('admin.access-lists.lists-index', compact('lists'));
    }

    public function listsCreate()
    {
        return view('admin.access-lists.lists-form', ['list' => null]);
    }

    public function listsStore(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:150',
            'description'=> 'nullable|string|max:500',
            'match_type' => 'required|in:national_id,last_name,both',
            'is_active'  => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $list = AccessList::create($data);

        // Optional file import at creation time
        if ($request->hasFile('file')) {
            $result = $this->importer->import($list, $request->file('file'));
            return redirect()->route('admin.access-lists.lists.index')
                ->with('success', "لیست ایجاد شد. {$result['inserted']} ردیف وارد شد، {$result['skipped']} رد شد.");
        }

        return redirect()->route('admin.access-lists.lists.index')
            ->with('success', 'لیست دسترسی ایجاد شد.');
    }

    public function listsEdit(AccessList $list)
    {
        return view('admin.access-lists.lists-form', compact('list'));
    }

    public function listsUpdate(Request $request, AccessList $list)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:150',
            'description'=> 'nullable|string|max:500',
            'match_type' => 'required|in:national_id,last_name,both',
            'is_active'  => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $list->update($data);

        return redirect()->route('admin.access-lists.lists.index')
            ->with('success', 'لیست به‌روز شد.');
    }

    public function listsDestroy(AccessList $list)
    {
        $list->delete();
        return redirect()->route('admin.access-lists.lists.index')
            ->with('success', 'لیست حذف شد.');
    }

    public function listsImport(Request $request, AccessList $list)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $result = $this->importer->import($list, $request->file('file'));

        $msg = "{$result['inserted']} ردیف وارد شد، {$result['skipped']} رد شد.";
        if (!empty($result['errors'])) {
            $msg .= ' خطاها: ' . implode(' | ', $result['errors']);
        }

        return redirect()->route('admin.access-lists.lists.index')
            ->with('success', $msg);
    }

    // ── Gates ──────────────────────────────────────────────────────────────

    public function gatesIndex()
    {
        $gates = AccessGate::with('accessList')->latest()->get();
        return view('admin.access-lists.gates-index', compact('gates'));
    }

    public function gatesCreate()
    {
        $lists = AccessList::where('is_active', true)->orderBy('name')->get();
        return view('admin.access-lists.gates-form', ['gate' => null, 'lists' => $lists]);
    }

    public function gatesStore(Request $request)
    {
        $data = $request->validate([
            'access_list_id'   => 'required|exists:access_lists,id',
            'name'             => 'required|string|max:150',
            'slug'             => 'required|string|max:100|unique:access_gates,slug',
            'description'      => 'nullable|string|max:500',
            'redirect_url'     => 'required|url|max:500',
            'session_duration' => 'required|integer|min:5|max:43200',
            'is_active'        => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['slug']      = Str::slug($data['slug'], '-');
        AccessGate::create($data);

        return redirect()->route('admin.access-lists.gates.index')
            ->with('success', 'گیت دسترسی ایجاد شد.');
    }

    public function gatesEdit(AccessGate $gate)
    {
        $lists = AccessList::where('is_active', true)->orderBy('name')->get();
        return view('admin.access-lists.gates-form', compact('gate', 'lists'));
    }

    public function gatesUpdate(Request $request, AccessGate $gate)
    {
        $data = $request->validate([
            'access_list_id'   => 'required|exists:access_lists,id',
            'name'             => 'required|string|max:150',
            'slug'             => 'required|string|max:100|unique:access_gates,slug,' . $gate->id,
            'description'      => 'nullable|string|max:500',
            'redirect_url'     => 'required|url|max:500',
            'session_duration' => 'required|integer|min:5|max:43200',
            'is_active'        => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['slug']      = Str::slug($data['slug'], '-');
        $gate->update($data);

        return redirect()->route('admin.access-lists.gates.index')
            ->with('success', 'گیت ویرایش شد.');
    }

    public function gatesDestroy(AccessGate $gate)
    {
        $gate->delete();
        return redirect()->route('admin.access-lists.gates.index')
            ->with('success', 'گیت حذف شد.');
    }
}
