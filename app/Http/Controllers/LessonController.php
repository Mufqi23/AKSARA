<?php

namespace App\Http\Controllers;

use App\Models\TeacherAssignment;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(protected ClassController $classes) {}

    public function create(Request $r, TeacherAssignment $classroom)
    {
        return $this->classes->form($r, $classroom, 'lessons');
    }

    public function edit(Request $r, TeacherAssignment $classroom, int $id)
    {
        return $this->classes->form($r, $classroom, 'lessons', $id);
    }

    public function store(Request $r, TeacherAssignment $classroom)
    {
        return $this->classes->save($r, $classroom, 'lessons');
    }

    public function update(Request $r, TeacherAssignment $classroom, int $id)
    {
        return $this->classes->save($r, $classroom, 'lessons', $id);
    }

    public function destroy(Request $r, TeacherAssignment $classroom, int $id)
    {
        return $this->classes->delete($r, $classroom, 'lessons', $id);
    }
}

