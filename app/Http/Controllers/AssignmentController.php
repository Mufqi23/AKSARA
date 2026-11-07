<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\TeacherAssignment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(protected ClassController $classes) {}

    public function create(Request $r, TeacherAssignment $classroom)
    {
        return $this->classes->form($r, $classroom, 'assignments');
    }

    public function edit(Request $r, TeacherAssignment $classroom, int $id)
    {
        return $this->classes->form($r, $classroom, 'assignments', $id);
    }

    public function store(Request $r, TeacherAssignment $classroom)
    {
        return $this->classes->save($r, $classroom, 'assignments');
    }

    public function update(Request $r, TeacherAssignment $classroom, int $id)
    {
        return $this->classes->save($r, $classroom, 'assignments', $id);
    }

    public function destroy(Request $r, TeacherAssignment $classroom, int $id)
    {
        return $this->classes->delete($r, $classroom, 'assignments', $id);
    }

    public function show(Request $r, Assignment $assignment)
    {
        return $this->classes->assignment($r, $assignment);
    }

    public function submit(Request $r, Assignment $assignment)
    {
        return $this->classes->submit($r, $assignment);
    }

    public function grade(Request $r, AssignmentSubmission $submission)
    {
        return $this->classes->grade($r, $submission);
    }

    public function bulkGrade(Request $r, Assignment $assignment)
    {
        return $this->classes->bulkGrade($r, $assignment);
    }

    public function downloadAssignment(Request $r, int $id)
    {
        return $this->classes->download($r, 'assignments', $id);
    }

    public function downloadSubmission(Request $r, int $id)
    {
        return $this->classes->download($r, 'submissions', $id);
    }
}

