<?php

namespace App\Http\Controllers;

use App\Models\TeacherAssignment;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function __construct(protected ClassController $classes) {}

    public function create(Request $r, TeacherAssignment $classroom)
    {
        return $this->classes->form($r, $classroom, 'materials');
    }

    public function edit(Request $r, TeacherAssignment $classroom, int $id)
    {
        return $this->classes->form($r, $classroom, 'materials', $id);
    }

    public function store(Request $r, TeacherAssignment $classroom)
    {
        return $this->classes->save($r, $classroom, 'materials');
    }

    public function update(Request $r, TeacherAssignment $classroom, int $id)
    {
        return $this->classes->save($r, $classroom, 'materials', $id);
    }

    public function destroy(Request $r, TeacherAssignment $classroom, int $id)
    {
        return $this->classes->delete($r, $classroom, 'materials', $id);
    }

    public function download(Request $r, int $id)
    {
        return $this->classes->download($r, 'materials', $id);
    }
}

