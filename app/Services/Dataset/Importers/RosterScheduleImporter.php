<?php

namespace App\Services\Dataset\Importers;

use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\ScheduleConflicts;
use Illuminate\Support\Facades\Hash;

class RosterScheduleImporter
{
    use ImportHelpers;

    private const INITIAL_PASSWORD = 'CampusDemo!2026';

    public static function importTeachers(array $rows, string $sheetLabel = 'Data Guru'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];
        $hashedInitialPassword = null;

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $nip = trim((string) ($row['nip'] ?? ''));
            $name = trim((string) ($row['nama_lengkap'] ?? ''));
            $email = mb_strtolower(trim((string) ($row['email'] ?? '')), 'UTF-8');
            $statusRaw = mb_strtolower(trim((string) ($row['status_akun'] ?? 'active')), 'UTF-8');
            $status = in_array($statusRaw, ['inactive', 'nonaktif', 'suspended'], true) ? 'inactive' : 'active';

            if ($nip === '' || mb_strlen($nip) > 60 || $name === '' || mb_strlen($name) > 120 || mb_strlen($email) > 190 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "{$sheetLabel} baris {$line}: NIP, nama lengkap, dan format email wajib valid.";

                continue;
            }

            $byEmail = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();
            $byNip = Teacher::with(['user' => fn ($q) => $q->withTrashed()])
                ->whereRaw('LOWER(employee_number) = ?', [mb_strtolower($nip, 'UTF-8')])
                ->first();

            if ($byEmail && ($byEmail->trashed() || $byEmail->role !== 'teacher')) {
                $errors[] = "{$sheetLabel} baris {$line}: Email '{$email}' sudah dipakai oleh akun non-guru atau diarsipkan.";

                continue;
            }
            if ($byNip && $byEmail && $byNip->user_id !== $byEmail->id) {
                $errors[] = "{$sheetLabel} baris {$line}: NIP '{$nip}' dan email '{$email}' merujuk ke dua akun berbeda.";

                continue;
            }
            if ($byEmail && ! $byNip && Teacher::where('user_id', $byEmail->id)->exists()) {
                $errors[] = "{$sheetLabel} baris {$line}: Email '{$email}' sudah terdaftar dengan NIP guru yang berbeda.";

                continue;
            }

            if ($byNip && $byNip->user) {
                $emailTaken = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->where('id', '!=', $byNip->user_id)->exists();
                if ($emailTaken) {
                    $errors[] = "{$sheetLabel} baris {$line}: Email '{$email}' sudah digunakan akun lain.";

                    continue;
                }
                $byNip->user->update([
                    'name' => $name,
                    'email' => $email,
                    'status' => $status,
                    'email_verified_at' => $byNip->user->email_verified_at ?? ($status === 'active' ? now() : null),
                ]);
                $byNip->update(['employee_number' => $nip]);
                $updated++;
            } elseif ($byEmail) {
                $byEmail->update([
                    'name' => $name,
                    'status' => $status,
                    'email_verified_at' => $byEmail->email_verified_at ?? ($status === 'active' ? now() : null),
                ]);
                Teacher::create(['user_id' => $byEmail->id, 'employee_number' => $nip]);
                $updated++;
            } else {
                $hashedInitialPassword ??= Hash::make(self::INITIAL_PASSWORD);
                $newUser = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $hashedInitialPassword,
                    'role' => 'teacher',
                    'status' => $status,
                    'email_verified_at' => $status === 'active' ? now() : null,
                ]);
                $newUser->saveAdminVisiblePassword(self::INITIAL_PASSWORD);
                Teacher::create(['user_id' => $newUser->id, 'employee_number' => $nip]);
                $created++;
            }
        }

        return compact('created', 'updated', 'errors');
    }

    public static function importStudents(array $rows, string $sheetLabel = 'Data Siswa'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];
        $hashedInitialPassword = null;

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $nis = trim((string) ($row['nis'] ?? ''));
            $name = trim((string) ($row['nama_lengkap'] ?? ''));
            $email = mb_strtolower(trim((string) ($row['email'] ?? '')), 'UTF-8');
            $statusRaw = mb_strtolower(trim((string) ($row['status_akun'] ?? 'active')), 'UTF-8');
            $status = in_array($statusRaw, ['inactive', 'nonaktif', 'suspended'], true) ? 'inactive' : 'active';

            if ($nis === '' || mb_strlen($nis) > 60 || $name === '' || mb_strlen($name) > 120 || mb_strlen($email) > 190 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "{$sheetLabel} baris {$line}: NIS, nama lengkap, dan format email wajib valid.";

                continue;
            }

            $blkErr = [];
            $block = self::resolveBlock($row, $blkErr);
            if (! $block) {
                $errors[] = "{$sheetLabel} baris {$line}: ".implode(' ', $blkErr);

                continue;
            }

            $byEmail = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();
            $byNis = Student::with(['user' => fn ($q) => $q->withTrashed()])
                ->whereRaw('LOWER(student_number) = ?', [mb_strtolower($nis, 'UTF-8')])
                ->first();

            if ($byEmail && ($byEmail->trashed() || $byEmail->role !== 'student')) {
                $errors[] = "{$sheetLabel} baris {$line}: Email '{$email}' sudah dipakai oleh akun bukan siswa atau diarsipkan.";

                continue;
            }
            if ($byNis && $byEmail && $byNis->user_id !== $byEmail->id) {
                $errors[] = "{$sheetLabel} baris {$line}: NIS '{$nis}' dan email '{$email}' merujuk ke dua akun berbeda.";

                continue;
            }
            if ($byEmail && ! $byNis && Student::where('user_id', $byEmail->id)->exists()) {
                $errors[] = "{$sheetLabel} baris {$line}: Email '{$email}' sudah dimiliki siswa dengan NIS berbeda.";

                continue;
            }

            if ($byNis && $byNis->user) {
                $emailTaken = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->where('id', '!=', $byNis->user_id)->exists();
                if ($emailTaken) {
                    $errors[] = "{$sheetLabel} baris {$line}: Email '{$email}' sudah digunakan akun lain.";

                    continue;
                }
                $oldBlockId = (int) $byNis->block_id;
                $byNis->user->update([
                    'name' => $name,
                    'email' => $email,
                    'status' => $status,
                    'email_verified_at' => $byNis->user->email_verified_at ?? ($status === 'active' ? now() : null),
                ]);
                $byNis->update(['student_number' => $nis, 'block_id' => $block->id]);
                if ($oldBlockId && $oldBlockId !== (int) $block->id) {
                    $oldClassIds = TeacherAssignment::where('block_id', $oldBlockId)->pluck('id');
                    if ($oldClassIds->isNotEmpty()) {
                        Enrollment::where('student_id', $byNis->id)->whereIn('teacher_assignment_id', $oldClassIds)->delete();
                    }
                }
                $student = $byNis;
                $updated++;
            } elseif ($byEmail) {
                $byEmail->update([
                    'name' => $name,
                    'status' => $status,
                    'email_verified_at' => $byEmail->email_verified_at ?? ($status === 'active' ? now() : null),
                ]);
                $student = Student::create(['user_id' => $byEmail->id, 'student_number' => $nis, 'block_id' => $block->id]);
                $updated++;
            } else {
                $hashedInitialPassword ??= Hash::make(self::INITIAL_PASSWORD);
                $newUser = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $hashedInitialPassword,
                    'role' => 'student',
                    'status' => $status,
                    'email_verified_at' => $status === 'active' ? now() : null,
                ]);
                $newUser->saveAdminVisiblePassword(self::INITIAL_PASSWORD);
                $student = Student::create(['user_id' => $newUser->id, 'student_number' => $nis, 'block_id' => $block->id]);
                $created++;
            }

            $classIds = TeacherAssignment::where('block_id', $block->id)->pluck('id');
            foreach ($classIds as $cid) {
                Enrollment::firstOrCreate(['student_id' => $student->id, 'teacher_assignment_id' => $cid]);
            }
        }

        return compact('created', 'updated', 'errors');
    }

    public static function importTeacherAssignments(array $rows, string $sheetLabel = 'SK Mengajar'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $nip = trim((string) ($row['nip_guru'] ?? ''));
            $kodeMapel = trim((string) ($row['kode_mapel'] ?? ''));

            $teacher = self::resolveTeacherByNip($nip);
            if (! $teacher) {
                $errors[] = "{$sheetLabel} baris {$line}: Guru dengan NIP/email '{$nip}' belum terdaftar.";

                continue;
            }

            $blkErr = [];
            $block = self::resolveBlock($row, $blkErr);
            if (! $block) {
                $errors[] = "{$sheetLabel} baris {$line}: ".implode(' ', $blkErr);

                continue;
            }

            $subject = self::resolveSubjectForBlock($kodeMapel, $block);
            if (! $subject) {
                $errors[] = "{$sheetLabel} baris {$line}: Mata pelajaran '{$kodeMapel}' belum terdaftar.";

                continue;
            }
            if ($subject->program_id && ($subject->program_id !== $block->program_id || $subject->year_level_id !== $block->year_level_id || (int) $subject->semester !== (int) $block->semester)) {
                $errors[] = "{$sheetLabel} baris {$line}: Mata pelajaran '{$kodeMapel}' tidak sesuai dengan cakupan jurusan/tingkat/semester rombel '{$block->name}'.";

                continue;
            }

            $existing = TeacherAssignment::where('block_id', $block->id)->where('subject_id', $subject->id)->first();
            if ($existing) {
                $existing->update(['teacher_id' => $teacher->id]);
                $assignment = $existing;
                $updated++;
            } else {
                $assignment = TeacherAssignment::create([
                    'teacher_id' => $teacher->id,
                    'block_id' => $block->id,
                    'subject_id' => $subject->id,
                ]);
                $created++;
            }

            foreach (Student::where('block_id', $block->id)->pluck('id') as $sid) {
                Enrollment::firstOrCreate(['student_id' => $sid, 'teacher_assignment_id' => $assignment->id]);
            }
        }

        return compact('created', 'updated', 'errors');
    }

    public static function importSchedules(array $rows, string $sheetLabel = 'Jadwal Pelajaran'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $blkErr = [];
            $block = self::resolveBlock($row, $blkErr);
            if (! $block) {
                $errors[] = "{$sheetLabel} baris {$line}: ".implode(' ', $blkErr);

                continue;
            }

            $kodeMapel = trim((string) ($row['kode_mapel'] ?? ''));
            $subject = self::resolveSubjectForBlock($kodeMapel, $block);
            $classroom = $subject
                ? TeacherAssignment::with('block')->where('block_id', $block->id)->where('subject_id', $subject->id)->first()
                : null;

            if (! $classroom) {
                $errors[] = "{$sheetLabel} baris {$line}: Kelas mapel '{$kodeMapel}' pada rombel '{$block->name}' belum memiliki penugasan guru.";

                continue;
            }

            $res = self::persistScheduleRow($classroom, $row, $line, $sheetLabel, $errors);
            $created += $res['created'];
            $updated += $res['updated'];
        }

        return compact('created', 'updated', 'errors');
    }

    public static function importBundleClasses(array $rows, string $sheetLabel = '06_Kelas_dan_Jadwal'): array
    {
        $taResult = self::importTeacherAssignments($rows, $sheetLabel);
        $created = $taResult['created'];
        $updated = $taResult['updated'];
        $errors = $taResult['errors'];

        if ($errors) {
            return compact('created', 'updated', 'errors');
        }

        foreach ($rows as $row) {
            $hariRaw = trim((string) ($row['hari'] ?? ''));
            if ($hariRaw === '') {
                continue;
            }
            $line = (int) ($row['_row'] ?? 0);
            $blkErr = [];
            $block = self::resolveBlock($row, $blkErr);
            $subject = $block ? self::resolveSubjectForBlock((string) ($row['kode_mapel'] ?? ''), $block) : null;
            $classroom = ($block && $subject)
                ? TeacherAssignment::with('block')->where('block_id', $block->id)->where('subject_id', $subject->id)->first()
                : null;
            if ($classroom) {
                $res = self::persistScheduleRow($classroom, $row, $line, $sheetLabel, $errors);
                $created += $res['created'];
                $updated += $res['updated'];
            }
        }

        return compact('created', 'updated', 'errors');
    }

    private static function persistScheduleRow(TeacherAssignment $classroom, array $row, int $line, string $sheetLabel, array &$errors): array
    {
        $day = self::parseDayOfWeek((string) ($row['hari'] ?? ''));
        $start = self::parseTimeValue((string) ($row['jam_mulai'] ?? ''));
        $end = self::parseTimeValue((string) ($row['jam_selesai'] ?? ''));
        $room = trim((string) ($row['ruang_kelas'] ?? ''));

        if (! $day || ! $start || ! $end || $start >= $end || $room === '' || mb_strlen($room) > 80) {
            $errors[] = "{$sheetLabel} baris {$line}: Hari (Senin–Minggu), jam_mulai, jam_selesai (mulai < selesai), dan ruang_kelas (maks. 80 karakter) wajib valid.";

            return ['created' => 0, 'updated' => 0];
        }

        $existing = ClassSchedule::where('teacher_assignment_id', $classroom->id)
            ->where('day', $day)
            ->where('start_time', $start)
            ->first();

        $data = ['day' => $day, 'start_time' => $start, 'end_time' => $end, 'room' => $room];
        if (ScheduleConflicts::exists($classroom, $data, $existing?->id)) {
            $errors[] = "{$sheetLabel} baris {$line}: Bentrok jadwal pada hari ke-{$day} ({$start}–{$end}) untuk guru, rombel '{$classroom->block->name}', atau ruang '{$room}'.";

            return ['created' => 0, 'updated' => 0];
        }

        if ($existing) {
            $existing->update($data);

            return ['created' => 0, 'updated' => 1];
        }

        ClassSchedule::create($data + ['teacher_assignment_id' => $classroom->id]);

        return ['created' => 1, 'updated' => 0];
    }
}

