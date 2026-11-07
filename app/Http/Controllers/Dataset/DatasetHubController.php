<?php

namespace App\Http\Controllers\Dataset;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Block;
use App\Models\DatasetAuditLog;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\Access;
use App\Services\Dataset\DatasetRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DatasetHubController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless(DatasetRegistry::canOperate($user), 403);

        $classes = Access::classes($user)->get()->sortBy('label')->values();
        $selectedClassId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        $selectedClass = null;

        if ($selectedClassId !== null) {
            $selectedClass = $classes->firstWhere('id', $selectedClassId);
            abort_unless($selectedClass !== null, 403);
        }

        $modules = DatasetRegistry::visibleModules($user);
        $masterModules = array_filter($modules, fn ($m) => ($m['category'] ?? '') === 'master');
        $classroomModules = array_filter($modules, fn ($m) => ($m['category'] ?? '') === 'classroom');

        $classIds = $selectedClass ? collect([$selectedClass->id]) : $classes->pluck('id');
        $stats = $user->role === 'admin' && ! $selectedClass
            ? [
                'students' => Student::count(),
                'teachers' => Teacher::count(),
                'blocks' => Block::count(),
                'classes' => $classes->count(),
                'records' => Grade::count() + AttendanceRecord::count(),
            ]
            : [
                'students' => Enrollment::whereIn('teacher_assignment_id', $classIds)->distinct('student_id')->count('student_id'),
                'teachers' => $user->role === 'admin' ? 1 : 1,
                'blocks' => $classes->whereIn('id', $classIds)->pluck('block_id')->unique()->count(),
                'classes' => $classIds->count(),
                'records' => Grade::whereIn('teacher_assignment_id', $classIds)->count()
                    + AttendanceRecord::whereHas('session', fn ($q) => $q->whereIn('teacher_assignment_id', $classIds))->count(),
            ];

        $recentLogs = collect();
        if (Schema::hasTable('dataset_audit_logs')) {
            $recentLogs = DatasetAuditLog::with('user')
                ->when($user->role !== 'admin', fn ($q) => $q->where('user_id', $user->id))
                ->latest('id')
                ->limit(12)
                ->get();
        }

        return view('dataset.index', [
            'user' => $user,
            'classes' => $classes,
            'selectedClass' => $selectedClass,
            'selectedClassId' => $selectedClassId,
            'masterModules' => $masterModules,
            'classroomModules' => $classroomModules,
            'moduleTitles' => collect(DatasetRegistry::modules())->map(fn ($m) => $m['title'])->put('master_bundle', 'Paket Lengkap Data Sekolah (7 Lembar)')->all(),
            'stats' => $stats,
            'recentLogs' => $recentLogs,
            'activePreview' => $request->session()->get('dataset_preview'),
        ]);
    }
}

