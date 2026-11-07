<?php

namespace App\Services\Ai;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use App\Services\Access;

class EarlyWarningCalculator
{
    public static function scoreMetrics(
        ?float $attendanceRate,
        int $absentCount,
        int $missingOverdueCount,
        int $lateSubmissionCount,
        int $returnedCount,
        ?float $gradeAverage
    ): array {
        $attPts = match (true) {
            $attendanceRate === null => 0,
            $attendanceRate < 65 => 35,
            $attendanceRate < 75 => 28,
            $attendanceRate < 85 => 15,
            default => 0,
        };

        $absentPts = match (true) {
            $absentCount >= 3 => 15,
            $absentCount === 2 => 12,
            $absentCount === 1 => 5,
            default => 0,
        };

        $missingPts = match (true) {
            $missingOverdueCount >= 2 => 25,
            $missingOverdueCount === 1 => 15,
            default => 0,
        };
        $taskPts = min(25, $missingPts + ($lateSubmissionCount * 5) + ($returnedCount * 6));

        $gradePts = match (true) {
            $gradeAverage === null => 0,
            $gradeAverage < 55 => 25,
            $gradeAverage < 60 => 20,
            $gradeAverage < 70 => 12,
            default => 0,
        };

        $rawScore = min(100, $attPts + $absentPts + $taskPts + $gradePts);

        $isHigh = $rawScore >= 60
            || ($attendanceRate !== null && $attendanceRate < 75)
            || ($gradeAverage !== null && $gradeAverage < 55)
            || $missingOverdueCount >= 2
            || ($absentCount >= 2 && $missingOverdueCount >= 1);

        $isMedium = ! $isHigh && (
            $rawScore >= 30
            || ($attendanceRate !== null && $attendanceRate < 85)
            || ($gradeAverage !== null && $gradeAverage < 70)
            || $missingOverdueCount === 1
            || $absentCount >= 2
            || $returnedCount >= 1
        );

        $tier = $isHigh ? 'high' : ($isMedium ? 'medium' : 'low');
        $riskScore = $isHigh ? max(60, $rawScore) : ($isMedium ? max(30, $rawScore) : $rawScore);

        return [
            'risk_score' => $riskScore,
            'tier' => $tier,
            'tier_label' => match ($tier) {
                'high' => 'Risiko Tinggi',
                'medium' => 'Risiko Sedang',
                default => 'Terkendali',
            },
            'tier_badge' => match ($tier) {
                'high' => 'ews-badge-high',
                'medium' => 'ews-badge-medium',
                default => 'ews-badge-low',
            },
        ];
    }

    public static function analyzeCohort(
        User $viewer,
        ?int $classId = null,
        ?int $programId = null,
        ?int $blockId = null,
        ?string $tierFilter = null,
        ?string $search = null,
        bool $allowExternalAi = true
    ): array {
        $accessibleQuery = Access::classes($viewer);
        if ($classId) {
            abort_unless((clone $accessibleQuery)->whereKey($classId)->exists(), 403);
        }

        $scopedQuery = clone $accessibleQuery;
        if ($classId) {
            $scopedQuery->whereKey($classId);
        }
        if ($programId) {
            $scopedQuery->whereHas('block', fn ($q) => $q->where('program_id', $programId));
        }
        if ($blockId) {
            $scopedQuery->where('block_id', $blockId);
        }

        $scopedClasses = $scopedQuery->get();
        $classIds = $scopedClasses->pluck('id')->all();

        $students = empty($classIds) ? collect() : Student::whereHas('enrollments', fn ($q) => $q->whereIn('teacher_assignment_id', $classIds))
            ->whereHas('user', fn ($q) => $q->where('role', 'student'))
            ->when(filled($search), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('student_number', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->with([
                'user:id,name,email',
                'block.program:id,code,name',
                'block.yearLevel:id,name,level',
                'enrollments' => fn ($q) => $q->whereIn('teacher_assignment_id', $classIds),
            ])
            ->orderBy('student_number')
            ->get();

        $studentIds = $students->pluck('id')->all();
        $attendanceMap = empty($studentIds) ? collect() : AttendanceRecord::join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
            ->whereIn('attendance_sessions.teacher_assignment_id', $classIds)
            ->whereIn('attendance_records.student_id', $studentIds)
            ->selectRaw("attendance_records.student_id, SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as attended, SUM(CASE WHEN status IN ('present','late','absent') THEN 1 ELSE 0 END) as applicable, SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count, SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count, SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count")
            ->groupBy('attendance_records.student_id')
            ->get()
            ->keyBy('student_id');

        $assignments = empty($classIds) ? collect() : Assignment::whereIn('teacher_assignment_id', $classIds)
            ->where('status', 'published')
            ->get(['id', 'teacher_assignment_id', 'due_at', 'title']);

        $submissions = ($assignments->isEmpty() || empty($studentIds)) ? collect() : AssignmentSubmission::whereIn('assignment_id', $assignments->pluck('id'))
            ->whereIn('student_id', $studentIds)
            ->whereIn('id', AssignmentSubmission::selectRaw('MAX(id)')->groupBy('assignment_id', 'student_id'))
            ->get(['id', 'assignment_id', 'student_id', 'status', 'is_late'])
            ->groupBy('student_id');

        $gradeMap = empty($studentIds) ? collect() : Grade::whereIn('teacher_assignment_id', $classIds)
            ->whereIn('student_id', $studentIds)
            ->selectRaw('student_id, SUM(score) as total_score, SUM(total_points) as total_points')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $evaluated = $students->map(function (Student $student) use ($attendanceMap, $assignments, $submissions, $gradeMap, $scopedClasses) {
            $enrolledClassIds = $student->enrollments->pluck('teacher_assignment_id')->all();
            $att = $attendanceMap->get($student->id);
            $attRate = ($att && $att->applicable > 0) ? round($att->attended / $att->applicable * 100, 1) : null;
            $absentCount = (int) ($att->absent_count ?? 0);
            $lateCount = (int) ($att->late_count ?? 0);
            $excusedCount = (int) ($att->excused_count ?? 0);

            $studentAssignments = $assignments->whereIn('teacher_assignment_id', $enrolledClassIds);
            $studentSubs = ($submissions->get($student->id) ?? collect())->keyBy('assignment_id');

            $missingOverdue = $studentAssignments->filter(fn ($a) => $a->due_at->isPast() && ! $studentSubs->has($a->id))->count();
            $lateSubs = $studentSubs->filter(fn ($s) => (bool) $s->is_late)->count();
            $returnedSubs = $studentSubs->where('status', 'returned')->count();

            $gr = $gradeMap->get($student->id);
            $gradeAvg = ($gr && $gr->total_points > 0) ? round($gr->total_score / $gr->total_points * 100, 1) : null;

            $scored = self::scoreMetrics($attRate, $absentCount, $missingOverdue, $lateSubs, $returnedSubs, $gradeAvg);
            $metrics = array_merge($scored, [
                'student_id' => $student->id,
                'student_number' => $student->student_number,
                'name' => $student->user?->name ?? 'Siswa',
                'email' => $student->user?->email ?? '',
                'program_code' => $student->block?->program?->code ?? '-',
                'program_name' => $student->block?->program?->name ?? '-',
                'block_name' => $student->block?->name ?? '-',
                'primary_class_id' => $enrolledClassIds[0] ?? $scopedClasses->first()?->id,
                'enrolled_classes_count' => count($enrolledClassIds),
                'attendance_rate' => $attRate,
                'absent_count' => $absentCount,
                'late_count' => $lateCount,
                'excused_count' => $excusedCount,
                'missing_overdue_count' => $missingOverdue,
                'late_submission_count' => $lateSubs,
                'returned_count' => $returnedSubs,
                'grade_average' => $gradeAvg,
            ]);

            return array_merge($metrics, EarlyWarningNarrative::forStudent($metrics));
        })->sortByDesc('risk_score')->values();

        $counts = [
            'total' => $evaluated->count(),
            'high' => $evaluated->where('tier', 'high')->count(),
            'medium' => $evaluated->where('tier', 'medium')->count(),
            'low' => $evaluated->where('tier', 'low')->count(),
        ];

        $scopeLabel = $classId && $scopedClasses->first() ? $scopedClasses->first()->label : null;
        $executive = EarlyWarningNarrative::cohortExecutiveSummary($counts, $evaluated->where('tier', 'high'), $scopeLabel, $allowExternalAi);

        $filtered = in_array($tierFilter, ['high', 'medium', 'low'], true)
            ? $evaluated->where('tier', $tierFilter)->values()
            : $evaluated;

        return [
            'students' => $filtered,
            'all_students' => $evaluated,
            'counts' => $counts,
            'executive' => $executive,
            'scoped_classes' => $scopedClasses,
        ];
    }

    public static function analyzeStudent(User $user): array
    {
        $student = $user->student;
        $classes = Access::classes($user)->get();
        if (! $student || $classes->isEmpty()) {
            $emptyScore = self::scoreMetrics(null, 0, 0, 0, 0, null);
            $emptyMetrics = array_merge($emptyScore, [
                'attendance_rate' => null, 'absent_count' => 0, 'late_count' => 0, 'excused_count' => 0,
                'missing_overdue_count' => 0, 'late_submission_count' => 0, 'returned_count' => 0, 'grade_average' => null,
            ]);

            return ['overall' => array_merge($emptyMetrics, EarlyWarningNarrative::forStudent($emptyMetrics)), 'courses' => collect()];
        }

        $classIds = $classes->pluck('id')->all();

        $attendanceByClass = AttendanceRecord::join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
            ->whereIn('attendance_sessions.teacher_assignment_id', $classIds)
            ->where('attendance_records.student_id', $student->id)
            ->selectRaw("attendance_sessions.teacher_assignment_id as class_id, SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as attended, SUM(CASE WHEN status IN ('present','late','absent') THEN 1 ELSE 0 END) as applicable, SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count, SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count, SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count")
            ->groupBy('attendance_sessions.teacher_assignment_id')
            ->get()
            ->keyBy('class_id');

        $allAssignments = Assignment::whereIn('teacher_assignment_id', $classIds)
            ->where('status', 'published')
            ->get(['id', 'teacher_assignment_id', 'due_at']);

        $allSubs = $allAssignments->isEmpty()
            ? collect()
            : AssignmentSubmission::whereIn('assignment_id', $allAssignments->pluck('id'))
                ->where('student_id', $student->id)
                ->whereIn('id', AssignmentSubmission::selectRaw('MAX(id)')->where('student_id', $student->id)->groupBy('assignment_id'))
                ->get(['id', 'assignment_id', 'status', 'is_late'])
                ->keyBy('assignment_id');

        $gradesByClass = Grade::whereIn('teacher_assignment_id', $classIds)
            ->where('student_id', $student->id)
            ->selectRaw('teacher_assignment_id as class_id, SUM(score) as total_score, SUM(total_points) as total_points')
            ->groupBy('teacher_assignment_id')
            ->get()
            ->keyBy('class_id');

        $assignmentsByClass = $allAssignments->groupBy('teacher_assignment_id');

        $courses = $classes->map(function ($classroom) use ($attendanceByClass, $assignmentsByClass, $allSubs, $gradesByClass) {
            $att = $attendanceByClass->get($classroom->id);
            $attRate = ($att && $att->applicable > 0) ? round($att->attended / $att->applicable * 100, 1) : null;
            $absentCount = (int) ($att->absent_count ?? 0);

            $assignments = $assignmentsByClass->get($classroom->id, collect());
            $subs = $assignments->map(fn ($a) => $allSubs->get($a->id))->filter()->keyBy('assignment_id');

            $missingOverdue = $assignments->filter(fn ($a) => $a->due_at->isPast() && ! $subs->has($a->id))->count();
            $lateSubs = $subs->filter(fn ($s) => (bool) $s->is_late)->count();
            $returnedSubs = $subs->where('status', 'returned')->count();

            $gr = $gradesByClass->get($classroom->id);
            $gradeAvg = ($gr && $gr->total_points > 0) ? round($gr->total_score / $gr->total_points * 100, 1) : null;

            $scored = self::scoreMetrics($attRate, $absentCount, $missingOverdue, $lateSubs, $returnedSubs, $gradeAvg);
            $metrics = array_merge($scored, [
                'class_id' => $classroom->id,
                'subject_code' => $classroom->subject->code,
                'subject_name' => $classroom->subject->name,
                'teacher_name' => $classroom->teacher->user?->name ?? 'Guru Pengajar',
                'attendance_rate' => $attRate,
                'absent_count' => $absentCount,
                'late_count' => (int) ($att->late_count ?? 0),
                'excused_count' => (int) ($att->excused_count ?? 0),
                'missing_overdue_count' => $missingOverdue,
                'late_submission_count' => $lateSubs,
                'returned_count' => $returnedSubs,
                'grade_average' => $gradeAvg,
            ]);

            return array_merge($metrics, EarlyWarningNarrative::forStudent($metrics));
        })->sortByDesc('risk_score')->values();

        $totalAttended = (int) $attendanceByClass->sum('attended');
        $totalApplicable = (int) $attendanceByClass->sum('applicable');
        $overallAttRate = $totalApplicable > 0 ? round($totalAttended / $totalApplicable * 100, 1) : null;
        $overallAbsents = (int) $attendanceByClass->sum('absent_count');
        $overallLates = (int) $attendanceByClass->sum('late_count');
        $overallExcused = (int) $attendanceByClass->sum('excused_count');
        $overallMissing = $allAssignments->filter(fn ($a) => $a->due_at->isPast() && ! $allSubs->has($a->id))->count();
        $overallLateSubs = $allSubs->filter(fn ($s) => (bool) $s->is_late)->count();
        $overallReturned = $allSubs->where('status', 'returned')->count();
        $totalScore = (float) $gradesByClass->sum('total_score');
        $totalPoints = (float) $gradesByClass->sum('total_points');
        $overallGradeAvg = $totalPoints > 0 ? round($totalScore / $totalPoints * 100, 1) : null;

        $overallScored = self::scoreMetrics($overallAttRate, $overallAbsents, $overallMissing, $overallLateSubs, $overallReturned, $overallGradeAvg);
        $overallMetrics = array_merge($overallScored, [
            'student_id' => $student->id,
            'student_number' => $student->student_number,
            'name' => $user->name,
            'email' => $user->email,
            'program_code' => $student->block?->program?->code ?? '-',
            'program_name' => $student->block?->program?->name ?? '-',
            'block_name' => $student->block?->name ?? '-',
            'primary_class_id' => $classes->first()?->id,
            'enrolled_classes_count' => count($classIds),
            'attendance_rate' => $overallAttRate,
            'absent_count' => $overallAbsents,
            'late_count' => $overallLates,
            'excused_count' => $overallExcused,
            'missing_overdue_count' => $overallMissing,
            'late_submission_count' => $overallLateSubs,
            'returned_count' => $overallReturned,
            'grade_average' => $overallGradeAvg,
        ]);
        $overall = array_merge($overallMetrics, EarlyWarningNarrative::forStudent($overallMetrics));

        return ['overall' => $overall, 'courses' => $courses];
    }
}

