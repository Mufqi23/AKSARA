<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Notifications\PortalNotice;
use App\Services\Access;
use App\Services\AssignmentGrading;
use App\Services\ClassContent;
use App\Services\Files;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClassController extends Controller
{
    public function show(Request $r, TeacherAssignment $classroom)
    {
        Access::classroom($r->user(), $classroom);
        $r->validate(['q' => 'nullable|string|max:100']);
        $classroom->load('block.program', 'block.academicYear', 'subject', 'teacher.user');
        $student = $r->user()->role === 'student';
        $content = [];
        foreach (ClassContent::all() as $kind => [$model]) {
            if ($kind === 'quizzes' && ! config('lms.show_advanced_features')) {
                continue;
            }
            $content[$kind] = $model::where('teacher_assignment_id', $classroom->id)->when($student, fn ($q) => $q->where('status', 'published'))->when($r->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$r->q.'%'))->orderBy($kind === 'lessons' ? 'position' : 'id')->get();
        }
        $students = $student ? collect() : $classroom->enrollments()->with('student.user')->get()->pluck('student')->filter(fn ($s) => $s->user);
        if (! $student && $r->filled('q')) {
            $students = $students->filter(fn ($s) => str_contains(strtolower($s->user->name.' '.$s->student_number), strtolower($r->q)));
        }
        $sessions = $student ? collect() : AttendanceSession::where('teacher_assignment_id', $classroom->id)->orderByDesc('date')->get();

        return view('classes.show', compact('classroom', 'content', 'students', 'student', 'sessions'));
    }

    public function form(Request $r, TeacherAssignment $classroom, string $kind, ?int $id = null)
    {
        Access::classroom($r->user(), $classroom, true);
        [$model,$fields] = ClassContent::definition($kind);
        $row = $id ? $model::where('teacher_assignment_id', $classroom->id)->findOrFail($id) : new $model;
        if (! $id) {
            $row->status = 'draft';
            if ($kind === 'assignments') {
                $row->allow_text = true;
            }
            if ($kind === 'lessons') {
                $row->position = 1 + (int) $classroom->lessons()->max('position');
            }
        }

        return view('classes.form', compact('classroom', 'kind', 'fields', 'row'));
    }

    public function save(Request $r, TeacherAssignment $classroom, string $kind, ?int $id = null)
    {
        Access::classroom($r->user(), $classroom, true);
        [$model] = ClassContent::definition($kind);
        $row = $id ? $model::where('teacher_assignment_id', $classroom->id)->findOrFail($id) : new $model;
        $data = $r->validate(ClassContent::rules($kind, ! $id));
        unset($data['attachment']);
        $publish = $data['status'] === 'published' && $row->status !== 'published';
        $oldPath = $row->path ?? null;
        $replacedPath = null;
        DB::transaction(function () use ($row, $kind, $id, &$data, $r, $classroom, $oldPath, &$replacedPath) {
            if ($id) {
                $row->newQuery()->whereKey($id)->lockForUpdate()->first();
            }
            if ($kind === 'quizzes') {
                if ($id && $row->attempts()->exists()) {
                    throw ValidationException::withMessages(['quiz' => 'Kuis yang sudah dikerjakan siswa telah dikunci agar nilai tetap akurat. Silakan buat kuis baru.']);
                }
                if ($data['status'] === 'published' && (! $id || ! $row->questions()->exists())) {
                    throw ValidationException::withMessages(['status' => 'Simpan sebagai draf terlebih dahulu, tambahkan soal, lalu terbitkan kuis.']);
                }
            }
            if ($kind === 'assignments' && $id && $row->submissions()->where('score', '>', $data['total_points'])->exists()) {
                throw ValidationException::withMessages(['total_points' => 'Nilai maksimal tidak boleh lebih kecil dari nilai siswa yang sudah tercatat.']);
            }
            $uploaded = Files::upload($r);
            if ($id && ! empty($uploaded['path']) && $oldPath && $oldPath !== $uploaded['path']) {
                $replacedPath = $oldPath;
            }
            $data += $uploaded;
            $row->fill($data + ['teacher_assignment_id' => $classroom->id])->save();
            if ($kind === 'assignments') {
                Grade::where('source_type', 'assignment')->where('source_id', $row->id)->update(['title' => $row->title, 'total_points' => $row->total_points]);
            }
        });
        if ($replacedPath) {
            Storage::disk('local')->delete($replacedPath);
        }
        if ($publish && in_array($kind, ['assignments', 'quizzes'])) {
            $noticeLabel = $kind === 'assignments' ? 'Tugas baru' : 'Kuis baru';
            foreach ($classroom->enrollments()->with('student.user')->get() as $enrollment) {
                $enrollment->student->user?->notify(new PortalNotice($noticeLabel, $row->title, '/classes/'.$classroom->id));
            }
        }

        return redirect('/classes/'.$classroom->id)->with('success', 'Materi kelas berhasil disimpan.');
    }

    public function delete(Request $r, TeacherAssignment $classroom, string $kind, int $id)
    {
        Access::classroom($r->user(), $classroom, true);
        [$model] = ClassContent::definition($kind);
        $row = $model::where('teacher_assignment_id', $classroom->id)->findOrFail($id);
        $oldPath = in_array($kind, ['materials', 'assignments'], true) ? ($row->path ?? null) : null;
        try {
            DB::transaction(function () use ($row, $kind) {
                if ($kind === 'quizzes') {
                    abort_if($row->attempts()->exists(), 422, 'Kuis yang sudah dikerjakan siswa tidak dapat dihapus.');
                    $row->questions()->delete();
                }
                $row->delete();
            });
        } catch (QueryException $e) {
            return back()->withErrors(['content' => 'Item ini sudah memiliki hasil pekerjaan siswa sehingga tidak dapat dihapus. Ubah statusnya menjadi Draf bila ingin menyembunyikan.']);
        }
        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'Materi kelas berhasil dihapus.');
    }

    public function assignment(Request $r, Assignment $assignment)
    {
        Access::classroom($r->user(), $assignment->classroom);
        $student = $r->user()->role === 'student';
        abort_if($student && $assignment->status !== 'published', 404);
        $assignment->load('classroom.block.program', 'classroom.subject', 'classroom.teacher.user');
        $query = $assignment->submissions()->with('student.user')->when($student, fn ($q) => $q->where('student_id', $r->user()->student->id));
        if (! config('lms.show_advanced_features')) {
            $query->whereIn('id', AssignmentSubmission::selectRaw('MAX(id)')->where('assignment_id', $assignment->id)->groupBy('student_id'));
        }
        $submissions = $query->latest('id')->get();
        $enrollments = $student ? collect() : $assignment->classroom->enrollments()->with('student.user')->get();
        if (! $student) {
            $enrolledIds = $enrollments->pluck('student_id')->all();
            foreach ($submissions->unique('student_id') as $submission) {
                if (! in_array($submission->student_id, $enrolledIds)) {
                    $former = new Enrollment(['student_id' => $submission->student_id]);
                    $former->setRelation('student', $submission->student);
                    $former->setAttribute('former_enrollment', true);
                    $enrollments->push($former);
                }
            }
        }

        return view('classes.assignment', compact('assignment', 'submissions', 'student', 'enrollments'));
    }

    public function submit(Request $r, Assignment $assignment)
    {
        abort_unless($r->user()->role === 'student', 403);
        Access::classroom($r->user(), $assignment->classroom);
        abort_unless($assignment->status === 'published', 404);
        $r->validate(['answer' => ($assignment->allow_text ? 'nullable|string|max:100000' : 'prohibited'), 'attachment' => 'nullable|'.Files::RULE]);
        if (! $r->filled('answer') && ! $r->hasFile('attachment')) {
            throw ValidationException::withMessages(['answer' => 'Tuliskan jawaban atau unggah berkas tugas terlebih dahulu.']);
        }
        DB::transaction(function () use ($r, $assignment) {
            Student::whereKey($r->user()->student->id)->lockForUpdate()->first();
            $version = 1 + (int) $assignment->submissions()->where('student_id', $r->user()->student->id)->max('version');
            $late = now()->gt($assignment->due_at);
            $assignment->submissions()->create(['student_id' => $r->user()->student->id, 'version' => $version, 'answer' => $r->input('answer'), 'submitted_at' => now(), 'is_late' => $late, 'status' => $late ? 'late' : 'submitted'] + Files::upload($r));
        });

        return back()->with('success', config('lms.show_advanced_features') ? 'Tugas berhasil dikumpulkan. Riwayat pengumpulan sebelumnya tetap tersimpan.' : 'Tugas berhasil dikumpulkan.');
    }

    public function grade(Request $r, AssignmentSubmission $submission)
    {
        Access::classroom($r->user(), $submission->assignment->classroom, true);
        $data = $r->validate(AssignmentGrading::rules($submission->assignment));
        AssignmentGrading::save($submission, $data);
        AssignmentGrading::notify($submission);

        return back()->with('success', 'Nilai dan catatan guru berhasil disimpan.');
    }

    public function bulkGrade(Request $r, Assignment $assignment)
    {
        Access::classroom($r->user(), $assignment->classroom, true);
        $rules = ['grades' => 'required|array|min:1|max:500', 'grades.*' => 'required|array:score,feedback,status', 'expected_count' => 'nullable|integer|min:1|max:500'];
        foreach (AssignmentGrading::rules($assignment, true) as $field => $rule) {
            $rules['grades.*.'.$field] = ($r->filled('expected_count') ? 'present|' : '').$rule;
        }
        $data = $r->validate($rules, ['grades.*.score.present' => 'Sebagian data nilai tidak terkirim lengkap ke server. Tidak ada nilai yang diubah.',
            'grades.*.feedback.present' => 'Sebagian data nilai tidak terkirim lengkap ke server. Tidak ada nilai yang diubah.']);
        if ($r->filled('expected_count') && count($data['grades']) !== (int) $data['expected_count']) {
            throw ValidationException::withMessages(['grades' => 'Sebagian baris nilai tidak terkirim lengkap ke server. Muat ulang halaman lalu simpan kembali.']);
        }
        $count = AssignmentGrading::saveMany($r->user(), $assignment, $data['grades']);

        return back()->with('success', $count ? 'Grades saved for '.$count.' submissions.' : 'No grades changed. Enter a score for the submissions you want to grade.');
    }

    public function download(Request $r, string $kind, int $id)
    {
        if ($kind === 'submissions') {
            $row = AssignmentSubmission::findOrFail($id);
            Access::classroom($r->user(), $row->assignment->classroom);
            abort_if($r->user()->role === 'student' && $row->student_id !== $r->user()->student->id, 403);
        } else {
            abort_unless(in_array($kind, ['materials', 'assignments']), 404);
            [$model] = ClassContent::definition($kind);
            $row = $model::findOrFail($id);
            Access::classroom($r->user(), $row->classroom);
            abort_if($r->user()->role === 'student' && $row->status !== 'published', 404);
        }
        abort_unless($row->path && Storage::disk('local')->exists($row->path), 404);

        return Storage::disk('local')->download($row->path, $row->original_name, ['X-Content-Type-Options' => 'nosniff']);
    }
}
