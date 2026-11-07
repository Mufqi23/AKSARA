<?php

namespace App\Services\Dataset;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\Block;
use App\Models\ClassSchedule;
use App\Models\DatasetAuditLog;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Program;
use App\Models\QuizQuestion;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Models\YearLevel;
use App\Services\Access;
use App\Services\Ai\EarlyWarningCalculator;
use App\Services\ReportExport;
use Illuminate\Support\Facades\DB;

class DatasetExporter
{
    private const DAY_NAMES = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    public static function downloadTemplate(User $user, string $module, string $format = 'xlsx', ?int $classId = null)
    {
        if (! DatasetRegistry::canAccessModule($user, $module, 'import')) {
            abort(403);
        }

        if ($module === 'master_bundle') {
            $sheets = [];
            foreach (DatasetRegistry::masterBundleSheets() as $sheetName => $spec) {
                $sheets[] = [
                    'name' => $sheetName,
                    'headers' => $spec['headers'],
                    'rows' => $spec['sample'],
                ];
            }

            return SpreadsheetCodec::downloadXlsx($sheets, 'Template_Data_Sekolah_Terpadu_AKSARA.xlsx');
        }

        $meta = DatasetRegistry::modules()[$module] ?? abort(404);
        $headers = $meta['headers'];
        $rows = self::buildPrefilledTemplateRows($user, $module, $meta['sample'], $classId);
        $filename = 'Template_'.$module.'_AKSARA';

        return strtolower($format) === 'csv'
            ? SpreadsheetCodec::downloadCsv($headers, $rows, $filename.'.csv')
            : SpreadsheetCodec::downloadXlsx([['name' => $meta['title'], 'headers' => $headers, 'rows' => $rows]], $filename.'.xlsx');
    }

    public static function exportModule(User $user, string $module, string $format = 'xlsx', ?int $classId = null, ?string $ipAddress = null)
    {
        if (! DatasetRegistry::canAccessModule($user, $module, 'export')) {
            abort(403);
        }

        $meta = DatasetRegistry::modules()[$module] ?? abort(404);
        $headers = $meta['headers'];
        $rows = self::collectModuleRows($user, $module, $classId);
        $fmt = in_array(strtolower($format), ['xlsx', 'csv', 'pdf'], true) ? strtolower($format) : 'xlsx';
        $baseName = 'Dataset_'.$module.'_'.now()->format('Ymd_His');

        DatasetAuditLog::recordSafe([
            'user_id' => $user->id,
            'action' => 'export',
            'module' => $module,
            'format' => $fmt,
            'filename' => $baseName.'.'.$fmt,
            'created_count' => 0,
            'updated_count' => 0,
            'total_rows' => count($rows),
            'scope_label' => $classId ? 'Kelas ID #'.$classId : 'Sekolah',
            'ip_address' => $ipAddress,
        ]);

        if ($fmt === 'pdf') {
            return ReportExport::download('pdf', $meta['title'], $headers, $rows);
        }
        if ($fmt === 'csv') {
            return SpreadsheetCodec::downloadCsv($headers, $rows, $baseName.'.csv');
        }

        return SpreadsheetCodec::downloadXlsx([['name' => $meta['title'], 'headers' => $headers, 'rows' => $rows]], $baseName.'.xlsx');
    }

    private static function buildPrefilledTemplateRows(User $user, string $module, array $defaultSample, ?int $classId): array
    {
        if (! $classId || ! in_array($module, ['grades', 'attendance', 'lessons', 'assignments', 'quiz_questions'], true)) {
            return $defaultSample;
        }

        $classroom = Access::classes($user)->whereKey($classId)->first();
        if (! $classroom) {
            return $defaultSample;
        }

        $rombel = $classroom->block->name;
        $kodeMapel = $classroom->subject->code;

        if ($module === 'grades') {
            $latestAssignment = Assignment::where('teacher_assignment_id', $classroom->id)->latest('id')->first();
            $taskTitle = $latestAssignment?->title ?? 'Tugas 1';
            $enrollments = Enrollment::with('student.user')->where('teacher_assignment_id', $classroom->id)->get();
            if ($enrollments->isNotEmpty()) {
                return $enrollments->map(fn ($enr) => [
                    $rombel,
                    $kodeMapel,
                    $taskTitle,
                    $enr->student?->student_number ?? '',
                    $enr->student?->user?->name ?? '',
                    '',
                    '',
                    'graded',
                ])->values()->all();
            }
        }

        if ($module === 'attendance') {
            $enrollments = Enrollment::with('student.user')->where('teacher_assignment_id', $classroom->id)->get();
            if ($enrollments->isNotEmpty()) {
                $today = today()->format('Y-m-d');

                return $enrollments->map(fn ($enr) => [
                    $rombel,
                    $kodeMapel,
                    $today,
                    '08:00',
                    '09:30',
                    $enr->student?->student_number ?? '',
                    $enr->student?->user?->name ?? '',
                    'Hadir',
                    '0',
                    '',
                ])->values()->all();
            }
        }

        if (! empty($defaultSample[0])) {
            $sampleRow = $defaultSample[0];
            $sampleRow[0] = $rombel;
            $sampleRow[1] = $kodeMapel;

            return [$sampleRow];
        }

        return $defaultSample;
    }

    private static function collectModuleRows(User $user, string $module, ?int $classId): array
    {
        $classIds = Access::classes($user)
            ->when($classId, fn ($q) => $q->whereKey($classId))
            ->pluck('id');

        return match ($module) {
            'academic_years' => AcademicYear::orderByDesc('starts_on')->get()
                ->map(fn ($y) => [$y->name, $y->starts_on->format('Y-m-d'), $y->ends_on->format('Y-m-d')])
                ->all(),

            'programs_levels' => self::exportProgramsAndLevels(),

            'blocks' => Block::with(['academicYear', 'program', 'yearLevel'])->orderBy('id')->get()
                ->map(fn ($b) => [
                    $b->academicYear?->name ?? '',
                    (string) $b->semester,
                    $b->program?->code ?? '',
                    (string) ($b->yearLevel?->level ?? ''),
                    $b->name,
                ])->all(),

            'teachers' => Teacher::with('user')->orderBy('employee_number')->get()
                ->map(fn ($t) => [
                    $t->employee_number,
                    $t->user?->name ?? '',
                    $t->user?->email ?? '',
                    $t->user?->status ?? 'active',
                ])->all(),

            'students' => Student::with(['user', 'block.academicYear', 'block.program', 'block.yearLevel'])->orderBy('student_number')->get()
                ->map(fn ($s) => [
                    $s->student_number,
                    $s->user?->name ?? '',
                    $s->user?->email ?? '',
                    $s->block?->academicYear?->name ?? '',
                    (string) ($s->block?->semester ?? ''),
                    $s->block?->program?->code ?? '',
                    (string) ($s->block?->yearLevel?->level ?? ''),
                    $s->block?->name ?? '',
                    $s->user?->status ?? 'active',
                ])->all(),

            'subjects' => self::exportSubjects(),

            'teacher_assignments' => Access::classes($user)->get()
                ->map(fn ($ta) => [
                    $ta->teacher?->employee_number ?? '',
                    $ta->subject?->code ?? '',
                    $ta->block?->name ?? '',
                    $ta->block?->academicYear?->name ?? '',
                    (string) ($ta->block?->semester ?? ''),
                    $ta->block?->program?->code ?? '',
                    (string) ($ta->block?->yearLevel?->level ?? ''),
                ])->all(),

            'schedules' => ClassSchedule::with(['classroom.block.academicYear', 'classroom.subject'])
                ->whereIn('teacher_assignment_id', $classIds)
                ->orderBy('day')->orderBy('start_time')->get()
                ->map(fn ($sc) => [
                    $sc->classroom?->block?->name ?? '',
                    $sc->classroom?->subject?->code ?? '',
                    self::DAY_NAMES[$sc->day] ?? (string) $sc->day,
                    substr((string) $sc->start_time, 0, 5),
                    substr((string) $sc->end_time, 0, 5),
                    $sc->room,
                    $sc->classroom?->block?->academicYear?->name ?? '',
                    (string) ($sc->classroom?->block?->semester ?? ''),
                ])->all(),

            'announcements' => Announcement::with(['program', 'block'])->latest('id')->get()
                ->map(fn ($a) => [
                    $a->title,
                    $a->body,
                    $a->program?->code ?? '',
                    $a->block?->name ?? '',
                ])->all(),

            'lessons' => Lesson::with(['classroom.block', 'classroom.subject'])
                ->whereIn('teacher_assignment_id', $classIds)
                ->orderBy('teacher_assignment_id')->orderBy('position')->get()
                ->map(fn ($l) => [
                    $l->classroom?->block?->name ?? '',
                    $l->classroom?->subject?->code ?? '',
                    (string) $l->position,
                    $l->title,
                    $l->description ?? '',
                    $l->content,
                    $l->status,
                ])->all(),

            'assignments' => Assignment::with(['classroom.block', 'classroom.subject'])
                ->whereIn('teacher_assignment_id', $classIds)
                ->orderBy('teacher_assignment_id')->orderBy('due_at')->get()
                ->map(fn ($as) => [
                    $as->classroom?->block?->name ?? '',
                    $as->classroom?->subject?->code ?? '',
                    $as->title,
                    $as->description ?? '',
                    $as->instructions,
                    $as->due_at?->format('Y-m-d H:i') ?? '',
                    (string) $as->total_points,
                    $as->status,
                ])->all(),

            'grades' => self::exportGrades($classIds->all()),

            'attendance' => AttendanceRecord::with(['session.classroom.block', 'session.classroom.subject', 'student.user'])
                ->whereHas('session', fn ($q) => $q->whereIn('teacher_assignment_id', $classIds))
                ->orderByDesc('id')->get()
                ->map(fn ($r) => [
                    $r->session?->classroom?->block?->name ?? '',
                    $r->session?->classroom?->subject?->code ?? '',
                    $r->session?->date?->format('Y-m-d') ?? '',
                    substr((string) ($r->session?->start_time ?? ''), 0, 5),
                    substr((string) ($r->session?->end_time ?? ''), 0, 5),
                    $r->student?->student_number ?? '',
                    $r->student?->user?->name ?? '',
                    match ($r->status) {
                        'present' => 'Hadir',
                        'late' => 'Terlambat',
                        'excused' => 'Izin',
                        default => 'Alpa',
                    },
                    (string) ($r->minutes_late ?? 0),
                    $r->remarks ?? '',
                ])->all(),

            'quiz_questions' => QuizQuestion::with(['quiz.classroom.block', 'quiz.classroom.subject'])
                ->whereHas('quiz', fn ($q) => $q->whereIn('teacher_assignment_id', $classIds))
                ->orderBy('quiz_id')->orderBy('id')->get()
                ->map(fn ($qq) => [
                    $qq->quiz?->classroom?->block?->name ?? '',
                    $qq->quiz?->classroom?->subject?->code ?? '',
                    $qq->quiz?->title ?? '',
                    (string) ($qq->quiz?->time_limit ?? 20),
                    $qq->question,
                    $qq->option_a,
                    $qq->option_b,
                    $qq->option_c,
                    $qq->option_d,
                    strtoupper($qq->correct_answer),
                    (string) $qq->points,
                    $qq->quiz?->status ?? 'draft',
                ])->all(),

            'early_warning' => EarlyWarningCalculator::analyzeCohort($user, $classId)['students']
                ->map(fn ($st) => [
                    $st['student_number'],
                    $st['name'],
                    $st['program_code'],
                    $st['block_name'],
                    $st['attendance_rate'] !== null ? $st['attendance_rate'].'%' : 'N/A',
                    (string) $st['absent_count'],
                    (string) $st['missing_overdue_count'],
                    $st['grade_average'] !== null ? $st['grade_average'].'%' : 'N/A',
                    (string) $st['risk_score'],
                    $st['tier_label'],
                    $st['recommendation'] ?? '',
                ])->all(),

            default => [],
        };
    }

    private static function exportProgramsAndLevels(): array
    {
        $programs = Program::orderBy('code')->get()->values();
        $levels = YearLevel::orderBy('level')->get()->values();
        $max = max($programs->count(), $levels->count());
        $rows = [];
        for ($i = 0; $i < $max; $i++) {
            $p = $programs->get($i);
            $l = $levels->get($i);
            $rows[] = [
                $p?->code ?? '',
                $p?->name ?? '',
                $l ? (string) $l->level : '',
                $l?->name ?? '',
            ];
        }

        return $rows;
    }

    private static function exportSubjects(): array
    {
        $prereqMap = DB::table('subject_prerequisites as sp')
            ->join('subjects as pr', 'pr.id', '=', 'sp.prerequisite_id')
            ->select('sp.subject_id', 'pr.code')
            ->get()
            ->groupBy('subject_id')
            ->map(fn ($items) => $items->pluck('code')->implode('; '));

        return Subject::with(['program', 'yearLevel'])->orderBy('code')->get()
            ->map(fn ($s) => [
                $s->code,
                $s->name,
                (string) $s->units,
                $s->lecture_units !== null ? (string) $s->lecture_units : '',
                $s->laboratory_units !== null ? (string) $s->laboratory_units : '',
                $s->program?->code ?? '',
                $s->yearLevel ? (string) $s->yearLevel->level : '',
                $s->semester !== null ? (string) $s->semester : '',
                (string) ($prereqMap->get($s->id) ?? ''),
                $s->description ?? '',
                $s->status,
            ])->all();
    }

    private static function exportGrades(array $classIds): array
    {
        if (empty($classIds)) {
            return [];
        }

        $latestSubIds = AssignmentSubmission::selectRaw('MAX(id) as id')
            ->whereHas('assignment', fn ($q) => $q->whereIn('teacher_assignment_id', $classIds))
            ->groupBy('assignment_id', 'student_id')
            ->pluck('id');

        return AssignmentSubmission::with(['assignment.classroom.block', 'assignment.classroom.subject', 'student.user'])
            ->whereIn('id', $latestSubIds)
            ->orderBy('assignment_id')
            ->get()
            ->map(fn ($sub) => [
                $sub->assignment?->classroom?->block?->name ?? '',
                $sub->assignment?->classroom?->subject?->code ?? '',
                $sub->assignment?->title ?? '',
                $sub->student?->student_number ?? '',
                $sub->student?->user?->name ?? '',
                $sub->score !== null ? (string) $sub->score : '',
                $sub->feedback ?? '',
                $sub->status,
            ])->all();
    }
}

