<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\Block;
use App\Models\Program;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;

class LandingController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            return redirect('/dashboard');
        }

        $stats = array_fill_keys([
            'students', 'teachers', 'subjects', 'programs', 'classes', 'blocks',
            'submissions', 'quizzes', 'quizzes_completed', 'announcements',
            'attendance_rate', 'attendance_total',
        ], 0);
        $activeYear = null;

        try {
            $attTotal = AttendanceRecord::where('status', '!=', 'excused')->count();
            $attPresent = AttendanceRecord::whereIn('status', ['present', 'late', 'hadir'])->count();

            $stats = [
                'students' => Student::count(),
                'teachers' => Teacher::count(),
                'subjects' => Subject::count(),
                'programs' => Program::count(),
                'classes' => TeacherAssignment::count(),
                'blocks' => Block::count(),
                'submissions' => AssignmentSubmission::count(),
                'quizzes' => Quiz::count(),
                'quizzes_completed' => QuizAttempt::whereNotNull('completed_at')->count(),
                'announcements' => Announcement::count(),
                'attendance_rate' => $attTotal > 0 ? round(($attPresent / $attTotal) * 100, 1) : 0,
                'attendance_total' => $attTotal,
            ];

            $activeYear = AcademicYear::whereDate('starts_on', '<=', today())
                ->whereDate('ends_on', '>=', today())
                ->latest('starts_on')
                ->first()
                ?? AcademicYear::latest('starts_on')->first();
        } catch (\Throwable) {
            // Gunakan nilai default apabila basis data belum siap
        }

        return view('landing', compact('stats', 'activeYear'));
    }
}
