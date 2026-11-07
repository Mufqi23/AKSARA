<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Services\Access;
use App\Services\AttendanceService;
use App\Services\ReportExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportController extends Controller
{
    public function attendance(Request $r)
    {
        return $this->index($r, 'attendance');
    }

    public function grades(Request $r)
    {
        return $this->index($r, 'grades');
    }

    public function students(Request $r)
    {
        return $this->index($r, 'students');
    }

    public function enrollments(Request $r)
    {
        return $this->index($r, 'enrollments');
    }

    public function index(Request $r, string $kind = 'attendance')
    {
        abort_unless(in_array($kind, ['attendance', 'grades', 'students', 'enrollments']), 404);
        $user = $r->user();
        $classes = Access::classes($user)->get();
        $ids = $classes->pluck('id');
        $r->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from', 'status' => 'nullable|in:present,late,absent,excused', 'format' => 'nullable|in:pdf,xlsx', 'month' => 'nullable|date_format:Y-m']);
        $selected = TeacherAssignment::whereIn('id', $ids);
        foreach (['block_id', 'subject_id', 'teacher_id'] as $field) {
            if ($r->filled($field)) {
                $selected->where($field, $r->input($field));
            }
        }
        foreach (['academic_year_id', 'program_id', 'year_level_id', 'semester'] as $field) {
            if ($r->filled($field)) {
                $selected->whereHas('block', fn ($q) => $q->where($field, $r->input($field)));
            }
        }
        if ($r->filled('class_id')) {
            $selected->whereKey($r->class_id);
        }
        $filteredIds = $selected->pluck('id');
        if ($kind === 'attendance') {
            $q = AttendanceRecord::with(['session.classroom.subject', 'session.classroom.block.program', 'session.classroom.teacher.user', 'student.user', 'excuse'])->whereHas('session', fn ($q) => $q->whereIn('teacher_assignment_id', $filteredIds));
            if ($r->filled('status')) {
                $q->where('status', $r->status);
            }
            if ($r->filled('from')) {
                $q->whereHas('session', fn ($q) => $q->whereDate('date', '>=', $r->from));
            }
            if ($r->filled('to')) {
                $q->whereHas('session', fn ($q) => $q->whereDate('date', '<=', $r->to));
            }
        } elseif ($kind === 'grades') {
            $q = Grade::with(['classroom.subject', 'classroom.block.program', 'student.user'])->whereIn('teacher_assignment_id', $filteredIds);
        } else {
            $q = Enrollment::with(['classroom.subject', 'classroom.block.program', 'student.user'])->whereIn('teacher_assignment_id', $filteredIds);
        }
        if ($user->role === 'student') {
            $q->where('student_id', $user->student?->id ?? 0);
        } elseif ($r->filled('student_id')) {
            $q->where('student_id', $r->student_id);
        }
        $all = $q->get();
        if ($kind === 'students') {
            $all = $all->unique('student_id')->values();
        }
        $rate = $kind === 'attendance' ? AttendanceService::rate($all) : null;
        $headers = match ($kind) {
            'attendance' => ['Nama Siswa', 'Rombel / Mata Pelajaran', 'Guru Pengajar', 'Tanggal', 'Status', 'Keterangan'],
            'grades' => ['Nama Siswa', 'Rombel / Mata Pelajaran', 'Penilaian / Tugas', 'Nilai', 'Nilai Maksimal'],
            default => ['NIS', 'Nama Siswa', 'Rombel / Mata Pelajaran', 'Email'],
        };
        $statusLabels = [
            'present' => 'Hadir',
            'late' => 'Terlambat',
            'absent' => 'Alpa',
            'excused' => 'Izin / Sakit',
        ];
        $rows = $all->map(function ($row) use ($kind, $statusLabels) {
            $name = $row->student->user?->name ?? 'Akun Diarsipkan';
            if ($kind === 'attendance') {
                $statusKey = strtolower((string) $row->status);

                return [$name, $row->session->classroom->label, $row->session->classroom->teacher->user?->name, $row->session->date->format('Y-m-d'), $statusLabels[$statusKey] ?? ucfirst($row->status), $row->remarks];
            }
            if ($kind === 'grades') {
                return [$name, $row->classroom->label, $row->title, $row->score, $row->total_points];
            }

            return [$row->student->student_number, $name, $row->classroom->label, $row->student->user?->email];
        })->all();
        if ($r->filled('format')) {
            return ReportExport::download($r->format, $kind, $headers, $rows);
        }
        $page = max(1, (int) $r->input('page', 1));
        $records = new LengthAwarePaginator($all->slice(($page - 1) * 25, 25), $all->count(), 25, $page, ['path' => $r->url(), 'query' => $r->query()]);
        $month = Carbon::parse(($r->input('month') ?: now()->format('Y-m')).'-01');
        $calendar = $kind === 'attendance' ? $all->groupBy(fn ($x) => $x->session->date->format('Y-m-d')) : collect();
        $summaries = $kind === 'attendance' ? $all->groupBy(fn ($x) => $x->session->classroom->label)->map(fn ($group) => AttendanceService::rate($group)) : collect();
        $studentOptions = $user->role === 'student'
            ? collect()
            : Student::with('user')->whereHas('enrollments', fn ($q) => $q->whereIn('teacher_assignment_id', $ids))->orderBy('student_number')->get();

        return view('reports.index', compact('kind', 'classes', 'headers', 'rows', 'all', 'records', 'rate', 'month', 'calendar', 'summaries', 'studentOptions'));
    }

    public function gradebook(Request $r, TeacherAssignment $classroom)
    {
        Access::classroom($r->user(), $classroom);
        $classroom->load('block.program', 'subject');
        $student = $r->user()->role === 'student';
        $enrollments = $classroom->enrollments()->with('student.user')->when($student, fn ($q) => $q->where('student_id', $r->user()->student->id))->get();
        $items = collect();
        foreach ($classroom->assignments()->when($student, fn ($q) => $q->where('status', 'published'))->get() as $item) {
            $items->push(['key' => 'assignment-'.$item->id, 'title' => $item->title, 'total' => (float) $item->total_points]);
        }
        foreach ($classroom->quizzes()->withSum('questions', 'points')->when($student, fn ($q) => $q->where('status', 'published'))->get() as $item) {
            $items->push(['key' => 'quiz-'.$item->id, 'title' => $item->title, 'total' => (float) ($item->questions_sum_points ?? 0)]);
        }
        $grades = Grade::where('teacher_assignment_id', $classroom->id)->when($student, fn ($q) => $q->where('student_id', $r->user()->student->id))->get()->groupBy('student_id');

        return view('reports.gradebook', compact('classroom', 'enrollments', 'items', 'grades'));
    }
}
