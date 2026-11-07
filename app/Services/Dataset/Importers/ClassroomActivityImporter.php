<?php

namespace App\Services\Dataset\Importers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceLog;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;

class ClassroomActivityImporter
{
    use ImportHelpers;

    public static function importLessons(array $rows, User $user, ?int $forcedClassId = null, string $sheetLabel = 'Silabus Pertemuan'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $clsErr = [];
            $classroom = self::resolveClassroomForUser($user, $row, $forcedClassId, $clsErr);
            if (! $classroom) {
                $errors[] = "{$sheetLabel} baris {$line}: ".implode(' ', $clsErr);

                continue;
            }

            $posRaw = trim((string) ($row['urutan_pertemuan'] ?? ''));
            $title = trim((string) ($row['judul_topik'] ?? ''));
            $desc = trim((string) ($row['ringkasan'] ?? '')) ?: null;
            $content = trim((string) ($row['isi_materi'] ?? ''));
            $status = self::parsePublishStatus((string) ($row['status'] ?? 'published'));

            if (filter_var($posRaw, FILTER_VALIDATE_INT) === false || (int) $posRaw < 1 || (int) $posRaw > 200 || $title === '' || mb_strlen($title) > 180 || $content === '' || ! $status) {
                $errors[] = "{$sheetLabel} baris {$line}: Urutan pertemuan (1–200), judul_topik (maks. 180 karakter), isi_materi, dan status (published/draft) wajib valid.";

                continue;
            }

            $pos = (int) $posRaw;
            $existing = Lesson::where('teacher_assignment_id', $classroom->id)->where('position', $pos)->first();
            $payload = ['title' => $title, 'description' => $desc, 'content' => $content, 'status' => $status];

            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                Lesson::create($payload + ['teacher_assignment_id' => $classroom->id, 'position' => $pos]);
                $created++;
            }
        }

        return compact('created', 'updated', 'errors');
    }

    public static function importAssignments(array $rows, User $user, ?int $forcedClassId = null, string $sheetLabel = 'Daftar Tugas'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $clsErr = [];
            $classroom = self::resolveClassroomForUser($user, $row, $forcedClassId, $clsErr);
            if (! $classroom) {
                $errors[] = "{$sheetLabel} baris {$line}: ".implode(' ', $clsErr);

                continue;
            }

            $title = trim((string) ($row['judul_tugas'] ?? ''));
            $desc = trim((string) ($row['deskripsi'] ?? '')) ?: null;
            $instructions = trim((string) ($row['instruksi'] ?? ''));
            $dueAt = self::parseDateTimeValue((string) ($row['tenggat_waktu'] ?? ''));
            $pointsRaw = trim((string) ($row['poin_maksimal'] ?? ''));
            $status = self::parsePublishStatus((string) ($row['status'] ?? 'published'));

            if ($title === '' || mb_strlen($title) > 180 || $instructions === '' || ! $dueAt || ! is_numeric($pointsRaw) || (float) $pointsRaw <= 0 || (float) $pointsRaw > 1000 || ! $status) {
                $errors[] = "{$sheetLabel} baris {$line}: Judul tugas (maks. 180 karakter), instruksi, tenggat_waktu (YYYY-MM-DD HH:MM), poin_maksimal (1–1000), dan status wajib valid.";

                continue;
            }

            $existing = Assignment::where('teacher_assignment_id', $classroom->id)
                ->whereRaw('LOWER(title) = ?', [mb_strtolower($title, 'UTF-8')])
                ->first();

            $payload = [
                'title' => $title,
                'description' => $desc,
                'instructions' => $instructions,
                'due_at' => $dueAt,
                'total_points' => round((float) $pointsRaw, 2),
                'allow_text' => true,
                'status' => $status,
            ];

            if ($existing) {
                $existing->update($payload);
                Grade::where('source_type', 'assignment')
                    ->where('source_id', $existing->id)
                    ->update([
                        'title' => $existing->title,
                        'total_points' => $existing->total_points,
                    ]);
                $updated++;
            } else {
                Assignment::create($payload + ['teacher_assignment_id' => $classroom->id]);
                $created++;
            }
        }

        return compact('created', 'updated', 'errors');
    }

    public static function importGrades(array $rows, User $user, ?int $forcedClassId = null, string $sheetLabel = 'Buku Nilai'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $clsErr = [];
            $classroom = self::resolveClassroomForUser($user, $row, $forcedClassId, $clsErr);
            if (! $classroom) {
                $errors[] = "{$sheetLabel} baris {$line}: ".implode(' ', $clsErr);

                continue;
            }

            $taskTitle = trim((string) ($row['judul_tugas'] ?? ''));
            $nis = trim((string) ($row['nis'] ?? ''));
            $scoreRaw = trim((string) ($row['skor'] ?? ''));
            $feedback = trim((string) ($row['umpan_balik_guru'] ?? '')) ?: null;
            $statusRaw = mb_strtolower(trim((string) ($row['status_penilaian'] ?? 'graded')), 'UTF-8');
            $gradeStatus = in_array($statusRaw, ['returned', 'revisi', 'dikembalikan'], true) ? 'returned' : 'graded';

            if ($scoreRaw === '') {
                continue;
            }

            $assignment = Assignment::where('teacher_assignment_id', $classroom->id)
                ->whereRaw('LOWER(title) = ?', [mb_strtolower($taskTitle, 'UTF-8')])
                ->first();
            if (! $assignment) {
                $errors[] = "{$sheetLabel} baris {$line}: Tugas '{$taskTitle}' tidak ditemukan pada kelas '{$classroom->label}'.";

                continue;
            }

            $student = self::resolveStudentByNis($nis);
            if (! $student || ! Enrollment::where('teacher_assignment_id', $classroom->id)->where('student_id', $student->id)->exists()) {
                $errors[] = "{$sheetLabel} baris {$line}: Siswa dengan NIS '{$nis}' tidak terdaftar pada kelas '{$classroom->label}'.";

                continue;
            }

            if (! is_numeric($scoreRaw) || (float) $scoreRaw < 0 || (float) $scoreRaw > (float) $assignment->total_points) {
                $errors[] = "{$sheetLabel} baris {$line}: Skor '{$scoreRaw}' harus di antara 0 dan {$assignment->total_points}.";

                continue;
            }

            $score = round((float) $scoreRaw, 2);
            $latestSub = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('student_id', $student->id)
                ->orderByDesc('version')
                ->first();

            if ($latestSub) {
                $latestSub->update([
                    'score' => $score,
                    'feedback' => $feedback,
                    'status' => $gradeStatus,
                    'graded_at' => now(),
                ]);
                $updated++;
            } else {
                AssignmentSubmission::create([
                    'assignment_id' => $assignment->id,
                    'student_id' => $student->id,
                    'version' => 1,
                    'answer' => 'Penilaian tercatat melalui impor Buku Nilai kelas.',
                    'submitted_at' => now(),
                    'is_late' => false,
                    'status' => $gradeStatus,
                    'score' => $score,
                    'feedback' => $feedback,
                    'graded_at' => now(),
                ]);
                $created++;
            }

            Grade::updateOrCreate(
                ['student_id' => $student->id, 'source_type' => 'assignment', 'source_id' => $assignment->id],
                [
                    'teacher_assignment_id' => $classroom->id,
                    'title' => $assignment->title,
                    'score' => $score,
                    'total_points' => $assignment->total_points,
                ]
            );
        }

        return compact('created', 'updated', 'errors');
    }

    public static function importAttendance(array $rows, User $user, ?int $forcedClassId = null, string $sheetLabel = 'Presensi Harian'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];
        $defaultThreshold = (int) (Setting::where('key', 'late_threshold')->value('value') ?: 15);

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $clsErr = [];
            $classroom = self::resolveClassroomForUser($user, $row, $forcedClassId, $clsErr);
            if (! $classroom) {
                $errors[] = "{$sheetLabel} baris {$line}: ".implode(' ', $clsErr);

                continue;
            }

            $date = self::parseDateValue((string) ($row['tanggal'] ?? ''));
            $start = self::parseTimeValue((string) ($row['jam_mulai'] ?? ''));
            $end = self::parseTimeValue((string) ($row['jam_selesai'] ?? ''));
            $nis = trim((string) ($row['nis'] ?? ''));
            $status = self::parseAttendanceStatus((string) ($row['status_kehadiran'] ?? ''));
            $minLateRaw = trim((string) ($row['menit_terlambat'] ?? ''));
            $remarks = trim((string) ($row['keterangan'] ?? '')) ?: null;

            if (! $date || ! $start || ! $end || $start >= $end || ! $status) {
                $errors[] = "{$sheetLabel} baris {$line}: Tanggal, jam_mulai, jam_selesai, dan status_kehadiran (Hadir/Terlambat/Izin/Alpa) wajib valid.";

                continue;
            }

            if (Carbon::parse($date)->gt(today())) {
                $errors[] = "{$sheetLabel} baris {$line}: Tanggal presensi ({$date}) tidak boleh berada di masa depan.";

                continue;
            }

            if ($user->role === 'teacher' && Carbon::parse($date)->lt(today()->subDays(7))) {
                $errors[] = "{$sheetLabel} baris {$line}: Guru hanya dapat mengimpor/mengubah presensi maksimal 7 hari ke belakang ({$date}).";

                continue;
            }

            $student = self::resolveStudentByNis($nis);
            if (! $student || ! Enrollment::where('teacher_assignment_id', $classroom->id)->where('student_id', $student->id)->exists()) {
                $errors[] = "{$sheetLabel} baris {$line}: Siswa dengan NIS '{$nis}' tidak terdaftar pada kelas '{$classroom->label}'.";

                continue;
            }

            $session = AttendanceSession::firstOrCreate(
                ['teacher_assignment_id' => $classroom->id, 'date' => $date, 'start_time' => $start],
                ['end_time' => $end, 'late_threshold' => $defaultThreshold]
            );

            $minutesLate = null;
            if ($status === 'present') {
                $minutesLate = 0;
            } elseif ($status === 'late') {
                $minutesLate = (is_numeric($minLateRaw) && (int) $minLateRaw > 0) ? (int) $minLateRaw : $session->late_threshold;
            }

            $record = AttendanceRecord::where('attendance_session_id', $session->id)->where('student_id', $student->id)->first();
            $before = $record ? $record->only(['status', 'minutes_late', 'remarks']) : null;

            if ($record) {
                $record->update(['status' => $status, 'minutes_late' => $minutesLate, 'remarks' => $remarks]);
                $updated++;
            } else {
                $record = AttendanceRecord::create([
                    'attendance_session_id' => $session->id,
                    'student_id' => $student->id,
                    'status' => $status,
                    'minutes_late' => $minutesLate,
                    'remarks' => $remarks,
                ]);
                $created++;
            }

            AttendanceLog::create([
                'attendance_record_id' => $record->id,
                'user_id' => $user->id,
                'before' => $before,
                'after' => $record->only(['status', 'minutes_late', 'remarks']),
                'reason' => 'Sinkronisasi Impor Dataset Presensi Sekolah',
            ]);
        }

        return compact('created', 'updated', 'errors');
    }

    public static function importQuizQuestions(array $rows, User $user, ?int $forcedClassId = null, string $sheetLabel = 'Bank Soal Kuis'): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $clsErr = [];
            $classroom = self::resolveClassroomForUser($user, $row, $forcedClassId, $clsErr);
            if (! $classroom) {
                $errors[] = "{$sheetLabel} baris {$line}: ".implode(' ', $clsErr);

                continue;
            }

            $quizTitle = trim((string) ($row['judul_kuis'] ?? ''));
            $durationRaw = trim((string) ($row['durasi_menit'] ?? '20'));
            $questionText = trim((string) ($row['pertanyaan'] ?? ''));
            $optA = trim((string) ($row['opsi_a'] ?? ''));
            $optB = trim((string) ($row['opsi_b'] ?? ''));
            $optC = trim((string) ($row['opsi_c'] ?? ''));
            $optD = trim((string) ($row['opsi_d'] ?? ''));
            $ans = mb_strtolower(trim((string) ($row['kunci_jawaban'] ?? '')), 'UTF-8');
            $ptsRaw = trim((string) ($row['poin'] ?? ''));
            $status = self::parsePublishStatus((string) ($row['status_kuis'] ?? 'draft'), 'draft');

            if ($quizTitle === '' || mb_strlen($quizTitle) > 180 || $questionText === '' || $optA === '' || $optB === '' || $optC === '' || $optD === '' || ! in_array($ans, ['a', 'b', 'c', 'd'], true) || ! is_numeric($ptsRaw) || (float) $ptsRaw <= 0 || (float) $ptsRaw > 1000 || ! $status) {
                $errors[] = "{$sheetLabel} baris {$line}: Judul kuis, pertanyaan, opsi A–D, kunci_jawaban (A/B/C/D), dan poin (1–1000) wajib diisi.";

                continue;
            }

            $duration = (filter_var($durationRaw, FILTER_VALIDATE_INT) !== false && (int) $durationRaw >= 1) ? (int) $durationRaw : 20;
            $quiz = Quiz::where('teacher_assignment_id', $classroom->id)
                ->whereRaw('LOWER(title) = ?', [mb_strtolower($quizTitle, 'UTF-8')])
                ->first();

            if ($quiz && $quiz->attempts()->exists()) {
                $errors[] = "{$sheetLabel} baris {$line}: Kuis '{$quizTitle}' sudah dikerjakan siswa sehingga soalnya dikunci.";

                continue;
            }

            if (! $quiz) {
                $quiz = Quiz::create([
                    'teacher_assignment_id' => $classroom->id,
                    'title' => $quizTitle,
                    'description' => 'Evaluasi pilihan ganda terimpor melalui Pusat Dataset Sekolah.',
                    'time_limit' => $duration,
                    'max_attempts' => 1,
                    'available_from' => now()->subHour(),
                    'available_until' => now()->addDays(14),
                    'status' => $status,
                ]);
            } else {
                $quiz->update(['time_limit' => $duration, 'status' => $status]);
            }

            $existingQ = QuizQuestion::where('quiz_id', $quiz->id)
                ->whereRaw('LOWER(question) = ?', [mb_strtolower($questionText, 'UTF-8')])
                ->first();

            $payload = [
                'option_a' => $optA,
                'option_b' => $optB,
                'option_c' => $optC,
                'option_d' => $optD,
                'correct_answer' => $ans,
                'points' => round((float) $ptsRaw, 2),
            ];

            if ($existingQ) {
                $existingQ->update($payload);
                $updated++;
            } else {
                QuizQuestion::create($payload + ['quiz_id' => $quiz->id, 'question' => $questionText]);
                $created++;
            }
        }

        return compact('created', 'updated', 'errors');
    }
}

