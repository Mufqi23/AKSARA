<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Services\Access;
use Illuminate\Http\Request;

class LearningHubController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'class_id' => 'nullable|integer|min:1',
            'q' => 'nullable|string|max:100',
            'tab' => 'nullable|in:lessons,materials,assignments',
        ]);

        $user = $request->user();
        $isStudent = $user->role === 'student';
        $isTeacher = $user->role === 'teacher';

        $classes = Access::classes($user)
            ->orderBy('block_id')
            ->orderBy('id')
            ->get();

        $allowedIds = $classes->pluck('id');
        $selectedClassId = $request->filled('class_id') && $allowedIds->contains((int) $request->input('class_id'))
            ? (int) $request->input('class_id')
            : null;

        $targetIds = $selectedClassId ? collect([$selectedClassId]) : $allowedIds;
        $search = trim((string) $request->input('q', ''));

        $lessons = Lesson::whereIn('teacher_assignment_id', $targetIds)
            ->when($isStudent, fn ($q) => $q->where('status', 'published'))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            }))
            ->with(['classroom.subject', 'classroom.block.program', 'classroom.teacher.user'])
            ->orderBy('teacher_assignment_id')
            ->orderBy('position')
            ->limit(60)
            ->get();

        $materials = LearningMaterial::whereIn('teacher_assignment_id', $targetIds)
            ->when($isStudent, fn ($q) => $q->where('status', 'published'))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            }))
            ->with(['classroom.subject', 'classroom.block.program', 'classroom.teacher.user'])
            ->latest('id')
            ->limit(60)
            ->get();

        $assignmentsQuery = Assignment::whereIn('teacher_assignment_id', $targetIds)
            ->when($isStudent, fn ($q) => $q->where('status', 'published'))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            }))
            ->with(['classroom.subject', 'classroom.block.program', 'classroom.teacher.user']);

        if (! $isStudent) {
            $latestPerStudent = AssignmentSubmission::selectRaw('MAX(id)')
                ->groupBy('assignment_id', 'student_id');

            $assignmentsQuery->withCount([
                'submissions as latest_submissions_count' => fn ($q) => $q->whereIn('id', $latestPerStudent),
                'submissions as ungraded_submissions_count' => fn ($q) => $q->whereIn('id', $latestPerStudent)->whereNull('graded_at'),
            ]);
        }

        $assignments = $assignmentsQuery
            ->orderBy('due_at')
            ->limit(60)
            ->get();

        $studentSubmissions = collect();
        if ($isStudent && $user->student && $assignments->isNotEmpty()) {
            $studentId = $user->student->id;
            $latestIds = AssignmentSubmission::selectRaw('MAX(id)')
                ->where('student_id', $studentId)
                ->whereIn('assignment_id', $assignments->pluck('id'))
                ->groupBy('assignment_id');

            $studentSubmissions = AssignmentSubmission::whereIn('id', $latestIds)
                ->get()
                ->keyBy('assignment_id');
        }

        $quizzes = config('lms.show_advanced_features')
            ? Quiz::whereIn('teacher_assignment_id', $targetIds)
                ->when($isStudent, fn ($q) => $q->where('status', 'published'))
                ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.$search.'%'))
                ->with(['classroom.subject', 'classroom.block.program'])
                ->orderBy('available_from')
                ->limit(30)
                ->get()
            : collect();

        $activeTab = $request->input('tab', 'lessons');
        if (! $request->filled('tab') && $search !== '') {
            if ($lessons->isEmpty() && $materials->isNotEmpty()) {
                $activeTab = 'materials';
            } elseif ($lessons->isEmpty() && $materials->isEmpty() && ($assignments->isNotEmpty() || $quizzes->isNotEmpty())) {
                $activeTab = 'assignments';
            }
        }

        return view('portal.learning-hub', compact(
            'classes',
            'selectedClassId',
            'search',
            'lessons',
            'materials',
            'assignments',
            'quizzes',
            'studentSubmissions',
            'isStudent',
            'isTeacher',
            'activeTab'
        ));
    }
}

