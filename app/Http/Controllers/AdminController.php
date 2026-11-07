<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogRequest;
use App\Models\AcademicYear;
use App\Models\Block;
use App\Models\Enrollment;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Notifications\Invitation;
use App\Services\Catalog;
use App\Services\ScheduleConflicts;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function index(Request $r, string $resource)
    {
        [$model,$fields] = Catalog::definition($resource);
        $q = $model::query();
        if ($resource === 'subjects') {
            $q->with(['program', 'yearLevel', 'prerequisites']);
        }
        if ($r->filled('q')) {
            $q->where(function ($q) use ($fields, $r) {
                foreach ($fields as $field => $type) {
                    if (in_array($type, ['text', 'email', 'textarea'])) {
                        $q->orWhere($field, 'like', '%'.$r->q.'%');
                    }
                }
            });
        }
        foreach ($fields as $field => $type) {
            if ($r->filled($field)) {
                $q->where($field, $r->input($field));
            }
        }

        $fieldChoices = collect($fields)->map(fn ($type) => Catalog::choices($type))->all();
        $rows = $q->latest('id')->paginate(15)->withQueryString();
        if ($resource === 'users') {
            User::preloadAdminVisiblePasswords($rows->getCollection());
        }

        return view('admin.index', ['resource' => $resource, 'fields' => $fields, 'fieldChoices' => $fieldChoices, 'rows' => $rows]);
    }

    public function form(string $resource, ?int $id = null)
    {
        [$model,$fields] = Catalog::definition($resource);
        $row = $id ? $model::findOrFail($id) : new $model;
        if ($row instanceof User && $row->role === 'admin') {
            abort(403, 'Akun administrator utama dikelola melalui perintah konsol server.');
        }

        $fieldChoices = collect($fields)->map(fn ($type) => Catalog::choices($type))->all();
        $missingRelated = collect($fields)->contains(fn ($type, $field) => ! ($resource === 'subjects' && in_array($field, ['program_id', 'year_level_id', 'semester'])) && ! in_array($type, ['text', 'email', 'date', 'time', 'textarea', 'number']) && empty($fieldChoices[$field]));
        if ($resource === 'subjects') {
            $row->load(['prerequisites.program', 'prerequisites.yearLevel']);
        }

        return view('admin.form', compact('resource', 'fields', 'row', 'fieldChoices', 'missingRelated'));
    }

    public function save(CatalogRequest $r, string $resource, ?int $id = null)
    {
        [$model] = Catalog::definition($resource);
        $row = $id ? $model::findOrFail($id) : new $model;
        $data = $r->validated();
        if ($resource === 'subjects' && $id && $row->program_id) {
            foreach (['program_id', 'year_level_id', 'semester', 'code'] as $scope) {
                if ((string) ($data[$scope] ?? '') !== (string) $row->$scope) {
                    throw ValidationException::withMessages([$scope => 'Gunakan menu Pusat Dataset untuk mengubah kode atau penempatan mata pelajaran kurikulum.']);
                }
            }
        }
        $plainPassword = null;
        if ($row instanceof User) {
            abort_if($row->role === 'admin', 403);
            if ($id && ($row->student || $row->teacher) && $row->role !== $data['role']) {
                throw ValidationException::withMessages(['role' => 'Hapus profil siswa/guru terlebih dahulu sebelum mengubah peran akun ini.']);
            }
            $hasCustomPassword = filled($data['password'] ?? null);
            if ($hasCustomPassword) {
                $plainPassword = (string) $data['password'];
            }
            if (! $id) {
                if ($hasCustomPassword) {
                    $data['email_verified_at'] = now();
                } else {
                    $data['password'] = Str::random(48);
                    $data['status'] = 'inactive';
                }
            } else {
                if ($hasCustomPassword) {
                    if (! $row->email_verified_at) {
                        $data['email_verified_at'] = now();
                    }
                } else {
                    unset($data['password']);
                }
            }
            if ($id && ! $row->email_verified_at && empty($data['email_verified_at']) && $data['status'] === 'active') {
                throw ValidationException::withMessages(['status' => 'Pengguna harus mengaktifkan akun melalui tautan undangan atau diberi kata sandi terlebih dahulu.']);
            }
            if ($id && $data['email'] !== $row->email && ! $hasCustomPassword) {
                $data['email_verified_at'] = null;
                $data['status'] = 'inactive';
            }
        }
        if (in_array($resource, ['students', 'teachers'])) {
            $user = User::findOrFail($data['user_id']);
            if ($user->role !== ($resource === 'students' ? 'student' : 'teacher')) {
                throw ValidationException::withMessages(['user_id' => 'Pilih akun pengguna dengan peran yang sesuai.']);
            }
        }
        if ($resource === 'enrollments') {
            if (Student::findOrFail($data['student_id'])->block_id !== TeacherAssignment::findOrFail($data['teacher_assignment_id'])->block_id) {
                throw ValidationException::withMessages(['student_id' => 'Siswa harus berasal dari rombel yang sama dengan kelas pelajaran ini.']);
            }
        }
        if ($resource === 'teacher-assignments') {
            $subject = Subject::findOrFail($data['subject_id']);
            $block = Block::findOrFail($data['block_id']);
            if ($subject->program_id && ($subject->program_id != $block->program_id || $subject->year_level_id != $block->year_level_id || $subject->semester != $block->semester)) {
                throw ValidationException::withMessages(['subject_id' => 'Pilih mata pelajaran yang sesuai dengan jurusan, tingkat kelas, dan semester rombel.']);
            }
        }
        try {
            DB::transaction(function () use ($row, $data, $resource, $id, $plainPassword) {
                if ($resource === 'schedules') {
                    $class = TeacherAssignment::findOrFail($data['teacher_assignment_id']);
                    AcademicYear::whereKey($class->block->academic_year_id)->lockForUpdate()->first();
                    $conflict = ScheduleConflicts::exists($class, $data, $id);
                    if ($conflict) {
                        throw ValidationException::withMessages(['start_time' => 'Jadwal bentrok dengan jam mengajar guru, rombel, atau ruangan yang sama.']);
                    }
                }
                if ($resource === 'teacher-assignments' && $id && $row->schedules()->exists() && ($row->teacher_id != $data['teacher_id'] || $row->block_id != $data['block_id'])) {
                    throw ValidationException::withMessages(['teacher_id' => 'Hapus jadwal kelas terlebih dahulu sebelum mengganti guru pengajar agar tidak terjadi bentrok jadwal.']);
                }
                if ($resource === 'teacher-assignments' && $id && $row->block_id != $data['block_id']) {
                    throw ValidationException::withMessages(['block_id' => 'Buat penugasan kelas baru untuk rombel yang berbeda agar riwayat nilai tetap terjaga.']);
                }
                $oldBlock = $resource === 'students' ? $row->block_id : null;
                $row->fill($data)->save();
                if ($row instanceof User && ! empty($plainPassword)) {
                    $row->saveAdminVisiblePassword($plainPassword);
                }
                if ($resource === 'students') {
                    if ($oldBlock && $oldBlock != $row->block_id) {
                        $row->enrollments()->delete();
                    }
                    foreach (TeacherAssignment::where('block_id', $row->block_id)->pluck('id') as $classId) {
                        Enrollment::firstOrCreate(['student_id' => $row->id, 'teacher_assignment_id' => $classId]);
                    }
                }
                if ($resource === 'teacher-assignments') {
                    foreach (Student::where('block_id', $row->block_id)->pluck('id') as $studentId) {
                        Enrollment::firstOrCreate(['student_id' => $studentId, 'teacher_assignment_id' => $row->id]);
                    }
                }
            });
        } catch (QueryException $e) {
            if (in_array($e->getCode(), ['23000', '23505'])) {
                return back()->withInput()->withErrors(['record' => 'Data yang sama sudah terdaftar atau masih terhubung dengan data lain.']);
            }
            throw $e;
        }
        if ($row instanceof User && ! $row->email_verified_at && $row->status === 'inactive') {
            try {
                $this->sendInvitation($row);
            } catch (\Throwable $e) {
                report($e);

                return redirect('/admin/manage/users')->withErrors(['email' => 'Akun berhasil disimpan, tetapi pengiriman email undangan gagal. Periksa pengaturan email lalu kirim ulang undangan.']);
            }
        }

        return redirect('/admin/manage/'.$resource)->with('success', 'Data berhasil disimpan.');
    }

    public function delete(Request $r, string $resource, int $id)
    {
        [$model] = Catalog::definition($resource);
        $row = $model::findOrFail($id);
        if ($row instanceof User) {
            abort_if($row->role === 'admin', 403);
            $row->update(['status' => 'suspended']);
        }
        try {
            $row->delete();
        } catch (QueryException $e) {
            return back()->withErrors(['record' => 'Data ini masih digunakan pada riwayat akademik. Hapus data terkait terlebih dahulu atau nonaktifkan akun pengguna.']);
        }

        return back()->with('success', 'Data berhasil dihapus.');
    }

    private function sendInvitation(User $user): void
    {
        $user->notify(new Invitation(Password::broker()->createToken($user)));
    }

    public function invite(User $user)
    {
        abort_unless($user->status === 'inactive' && ! $user->email_verified_at, 422);
        $this->sendInvitation($user);

        return back()->with('success', 'Email undangan aktivasi berhasil dikirim.');
    }

    public function settings()
    {
        return view('admin.settings', ['threshold' => Setting::where('key', 'late_threshold')->value('value') ?? 15]);
    }

    public function saveSettings(Request $r)
    {
        $r->validate(['late_threshold' => 'required|integer|min:1|max:120']);
        Setting::updateOrCreate(['key' => 'late_threshold'], ['value' => $r->late_threshold]);

        return back()->with('success', 'Batas waktu keterlambatan berhasil disimpan. Sesi kehadiran sebelumnya tetap memakai aturan awal.');
    }
}
