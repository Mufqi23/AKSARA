<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Block;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\YearLevel;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Catalog
{
    public static function all(): array
    {
        return [
            'users' => [User::class, ['name' => 'text', 'email' => 'email', 'role' => 'select:student,teacher', 'status' => 'select:inactive,active,suspended']],
            'academic-years' => [AcademicYear::class, ['name' => 'text', 'starts_on' => 'date', 'ends_on' => 'date']],
            'programs' => [Program::class, ['code' => 'text', 'name' => 'text']],
            'year-levels' => [YearLevel::class, ['name' => 'text', 'level' => 'number']],
            'blocks' => [Block::class, ['academic_year_id' => 'academic-years', 'program_id' => 'programs', 'year_level_id' => 'year-levels', 'name' => 'text', 'semester' => 'select:1,2,3']],
            'students' => [Student::class, ['user_id' => 'student-users', 'student_number' => 'text', 'block_id' => 'blocks']],
            'teachers' => [Teacher::class, ['user_id' => 'teacher-users', 'employee_number' => 'text']],
            'subjects' => [Subject::class, ['code' => 'text', 'name' => 'text', 'description' => 'textarea', 'units' => 'number', 'status' => 'select:active,inactive', 'program_id' => 'programs', 'year_level_id' => 'year-levels', 'semester' => 'select:1,2,3', 'lecture_units' => 'number', 'laboratory_units' => 'number']],
            'teacher-assignments' => [TeacherAssignment::class, ['teacher_id' => 'teachers', 'block_id' => 'blocks', 'subject_id' => 'subjects']],
            'enrollments' => [Enrollment::class, ['student_id' => 'students', 'teacher_assignment_id' => 'teacher-assignments']],
            'schedules' => [ClassSchedule::class, ['teacher_assignment_id' => 'teacher-assignments', 'day' => 'select:1,2,3,4,5,6,7', 'start_time' => 'time', 'end_time' => 'time', 'room' => 'text']],
        ];
    }

    public static function definition(string $resource): array
    {
        abort_unless(isset(self::all()[$resource]), 404);

        return self::all()[$resource];
    }

    public static function label($row): string
    {
        if ($row instanceof Subject) {
            return $row->catalog_label;
        }
        if ($row instanceof TeacherAssignment) {
            return $row->label.' · '.$row->block->academicYear->name.' / S'.$row->block->semester;
        }
        if ($row instanceof Block) {
            return $row->program->code.' '.$row->name.' · '.$row->academicYear->name.' / S'.$row->semester;
        }
        if ($row instanceof Student) {
            return $row->student_number.' · '.($row->user?->name ?? 'Akun diarsipkan');
        }
        if ($row instanceof Teacher) {
            return $row->employee_number.' · '.($row->user?->name ?? 'Akun diarsipkan');
        }

        return $row->name ?? $row->title ?? $row->code ?? ('#'.$row->id);
    }

    public static function choices(string $type): array
    {
        if (str_starts_with($type, 'select:')) {
            $values = explode(',', substr($type, 7));

            return array_combine($values, $values);
        }
        if (in_array($type, ['student-users', 'teacher-users'])) {
            return User::where('role', strtok($type, '-'))->pluck('name', 'id')->all();
        }
        if (isset(self::all()[$type])) {
            $relations = match ($type) {
                'teacher-assignments' => ['block.program', 'block.academicYear', 'subject'],
                'blocks' => ['program', 'academicYear'],
                'students', 'teachers' => ['user'],
                'subjects' => ['program', 'yearLevel'],
                default => [],
            };

            return self::all()[$type][0]::with($relations)->get()->mapWithKeys(fn ($row) => [$row->id => self::label($row)])->all();
        }

        return [];
    }

    public static function fieldLabel(string $field, ?string $resource = null): string
    {
        return match ($field) {
            'name' => $resource === 'year-levels' ? 'Year Level Name' : 'Name',
            'user_id' => 'User Account',
            'teacher_assignment_id' => 'Class',
            'allow_text' => 'Allow Text Submission',
            'due_at' => 'Due Date',
            'total_points' => 'Points',
            default => Str::headline(preg_replace('/_id$/', '', $field)),
        };
    }

    public static function fieldLabelId(string $field, ?string $resource = null): string
    {
        return match ($field) {
            'name' => match ($resource) {
                'year-levels' => 'Nama Tingkat Kelas',
                'academic-years' => 'Nama Tahun Ajaran',
                'programs' => 'Nama Jurusan / Program',
                'blocks' => 'Nama Rombel / Kelas',
                'subjects' => 'Nama Mata Pelajaran',
                'users' => 'Nama Lengkap',
                default => 'Nama',
            },
            'code' => match ($resource) {
                'programs' => 'Kode Jurusan',
                'subjects' => 'Kode Mapel',
                default => 'Kode',
            },
            'email' => 'Alamat Email',
            'role' => 'Peran Pengguna',
            'status' => 'Status',
            'starts_on' => 'Tanggal Mulai',
            'ends_on' => 'Tanggal Selesai',
            'level' => 'Angka Tingkat Kelas',
            'academic_year_id' => 'Tahun Ajaran',
            'program_id' => 'Jurusan / Program',
            'year_level_id' => 'Tingkat Kelas',
            'semester' => 'Semester',
            'user_id' => 'Akun Pengguna',
            'student_number' => 'NIS / Nomor Induk Siswa',
            'block_id' => 'Rombel / Kelas',
            'employee_number' => 'NIP / Nomor Induk Guru',
            'description' => 'Deskripsi',
            'units' => 'Jam Pelajaran (JP)',
            'lecture_units' => 'JP Teori',
            'laboratory_units' => 'JP Praktik',
            'teacher_id' => 'Guru Pengajar',
            'subject_id' => 'Mata Pelajaran',
            'student_id' => 'Siswa',
            'teacher_assignment_id' => 'Kelas Pelajaran',
            'day' => 'Hari Belajar',
            'start_time' => 'Jam Mulai',
            'end_time' => 'Jam Selesai',
            'room' => 'Ruang Kelas',
            'title' => 'Judul Materi / Tugas',
            'content' => 'Isi Modul / Materi',
            'instructions' => 'Petunjuk Pengerjaan',
            'attachment', 'file' => 'Berkas Lampiran',
            'due_at' => 'Batas Waktu Pengumpulan',
            'total_points', 'points' => 'Bobot Nilai Maksimal',
            'allow_text' => 'Izinkan Jawaban Teks Langsung',
            'time_limit' => 'Durasi Waktu (Menit)',
            'max_attempts' => 'Batas Percobaan Maksimal',
            'available_from' => 'Dibuka Mulai Tanggal',
            'available_until' => 'Ditutup Pada Tanggal',
            default => self::fieldLabel($field, $resource),
        };
    }

    public static function rules(string $resource, ?int $id, array $input = []): array
    {
        [$model,$fields] = self::definition($resource);
        $table = (new $model)->getTable();
        $rules = [];
        foreach ($fields as $field => $type) {
            $rules[$field] = ['required'];
            if ($type === 'textarea') {
                $rules[$field] = ['nullable', 'string', 'max:10000'];
            } elseif (in_array($type, ['date', 'time', 'email', 'number', 'text'])) {
                $rules[$field] = array_merge($rules[$field], match ($type) {
                    'date' => ['date'],'time' => ['date_format:H:i'],'email' => ['email', 'max:255'],'number' => ['integer', 'min:1', 'max:12'],default => ['string', 'max:120']
                });
            } elseif (str_starts_with($type, 'select:')) {
                $rules[$field][] = 'in:'.substr($type, 7);
            } else {
                $target = match ($type) {
                    'student-users','teacher-users' => 'users',default => (new (self::all()[$type][0]))->getTable()
                };
                $rules[$field][] = 'exists:'.$target.',id';
            }
        }
        foreach (['email', 'code', 'student_number', 'employee_number', 'user_id'] as $unique) {
            if (isset($fields[$unique])) {
                $uniqueRule = Rule::unique($table, $unique)->ignore($id);
                if ($resource === 'subjects' && $unique === 'code') {
                    foreach (['program_id', 'year_level_id', 'semester'] as $scope) {
                        $uniqueRule->where($scope, $input[$scope] ?? null);
                    }
                }
                $rules[$unique][] = $uniqueRule;
            }
        }
        if (in_array($resource, ['academic-years', 'year-levels'])) {
            $rules['name'][] = Rule::unique($table, 'name')->ignore($id);
        }
        if ($resource === 'year-levels') {
            $rules['level'][] = Rule::unique($table, 'level')->ignore($id);
        }
        if ($resource === 'subjects') {
            $rules['units'] = ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:12'];
            foreach (['program_id', 'year_level_id', 'semester'] as $scope) {
                $rules[$scope][0] = 'nullable';
                $other = implode(',', array_diff(['program_id', 'year_level_id', 'semester'], [$scope]));
                $rules[$scope][] = 'required_with:'.$other;
            }
            foreach (['lecture_units', 'laboratory_units'] as $units) {
                $rules[$units] = ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:12', 'required_with:'.($units === 'lecture_units' ? 'laboratory_units' : 'lecture_units')];
            }
        }
        if ($resource === 'academic-years') {
            $rules['ends_on'][] = 'after:starts_on';
        }
        if ($resource === 'schedules') {
            $rules['end_time'][] = 'after:start_time';
        }
        if ($resource === 'users') {
            $rules['password'] = ['nullable', 'string', 'min:8', 'max:255'];
        }

        return $rules;
    }
}
