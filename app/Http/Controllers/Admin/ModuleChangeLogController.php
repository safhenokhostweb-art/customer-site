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
        if ($request->boolean('sync')) {
            $updateCheck->syncModuleChangeLogs();
        }

        $logs = ModuleChangeLog::query()
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->paginate(50);

        $installedVersions = ModuleVersion::allVersions();

        return view('admin.modules.changelog', compact('logs', 'installedVersions'));
    }
}
