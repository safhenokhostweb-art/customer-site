<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModuleChangeLog;
use App\Models\ModuleVersion;
use App\Services\UpdateCheckService;
use Illuminate\Http\Request;

class ModuleChangeLogController extends Controller
{
    public function index(Request $request, UpdateCheckService $updateCheck)
    {
        $moduleSlug = trim((string) $request->query('module', ''));

        if ($request->boolean('sync')) {
            $updateCheck->syncModuleChangeLogs();
        }

        $logsQuery = ModuleChangeLog::query();
        if ($moduleSlug !== '') {
            $logsQuery->where('module_slug', $moduleSlug);
        }

        $logs = $logsQuery
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->paginate(50);

        $installedVersions = ModuleVersion::allVersions();

        return view('admin.modules.changelog', compact('logs', 'installedVersions', 'moduleSlug'));
    }
}
