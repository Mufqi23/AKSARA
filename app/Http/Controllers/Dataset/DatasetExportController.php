<?php

namespace App\Http\Controllers\Dataset;

use App\Http\Controllers\Controller;
use App\Services\Access;
use App\Services\Dataset\DatasetExporter;
use App\Services\Dataset\DatasetRegistry;
use Illuminate\Http\Request;

class DatasetExportController extends Controller
{
    public function download(Request $request, string $module)
    {
        $user = $request->user();
        abort_unless(DatasetRegistry::canAccessModule($user, $module, 'export'), 403);

        $classId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        if ($classId !== null) {
            abort_unless(Access::classes($user)->whereKey($classId)->exists(), 403);
        }

        $format = (string) $request->query('format', 'xlsx');

        return DatasetExporter::exportModule($user, $module, $format, $classId, $request->ip());
    }
}

