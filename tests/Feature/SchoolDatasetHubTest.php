<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\DatasetAuditLog;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\Access;
use App\Services\Dataset\SpreadsheetCodec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SchoolDatasetHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $role): User
    {
        return User::where('email', $role.'@example.com')->firstOrFail();
    }

    private function makeUploadedXlsx(array $sheets, string $name = 'dataset.xlsx'): UploadedFile
    {
        $tmpPath = SpreadsheetCodec::buildXlsxFile($sheets);

        return new UploadedFile(
            $tmpPath,
            $name,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    public function test_students_are_strictly_forbidden_from_dataset_hub_import_and_export(): void
    {
        $student = $this->user('student');
        $this->actingAs($student);

        $this->get('/dashboard')->assertOk()->assertDontSee('href="/dataset"', false);
        $this->get('/dataset')->assertForbidden();
        $this->get('/dataset/templates/grades?format=xlsx')->assertForbidden();
        $this->get('/dataset/export/grades?format=xlsx')->assertForbidden();
        $this->get('/dataset/export/students?format=csv')->assertForbidden();

        $csv = UploadedFile::fake()->createWithContent('grades.csv', "nama_rombel,kode_mapel,judul_tugas,nis,skor\n3J,PF102,Activity 1,DEMO-2026-0001,100\n");
        $this->post('/dataset/preview', ['module' => 'grades', 'file' => $csv])->assertForbidden();
        $this->post('/dataset/commit', ['preview_token' => 'invalid'])->assertForbidden();
    }

    public function test_teacher_access_is_strictly_limited_to_own_classes_and_classroom_modules(): void
    {
        $teacher = $this->user('teacher');
        $this->actingAs($teacher);

        $ownClass = Access::classes($teacher)->firstOrFail();
        $otherClass = TeacherAssignment::whereNotIn('id', Access::classes($teacher)->pluck('id'))->firstOrFail();

        $this->get('/dataset')->assertOk()
            ->assertSee('Pusat Ekspor & Impor Dataset Sekolah')
            ->assertSee('Rekap Buku Nilai Siswa')
            ->assertDontSee('Impor Data Akademik Sekolah Sekaligus')
            ->assertDontSee('Mode Kerjasama');

        // Forbidden on master modules and other teachers' classes
        $this->get('/dataset/export/students?format=xlsx')->assertForbidden();
        $this->get('/dataset/templates/master_bundle')->assertForbidden();
        $this->get('/dataset?class_id='.$otherClass->id)->assertForbidden();
        $this->get('/dataset/export/grades?format=xlsx&class_id='.$otherClass->id)->assertForbidden();

        // Allowed on own class template & export
        $this->get('/dataset/templates/grades?format=xlsx&class_id='.$ownClass->id)->assertOk();
        $this->get('/dataset/export/early_warning?format=pdf&class_id='.$ownClass->id)->assertOk();
    }

    public function test_admin_can_import_full_7_sheet_master_bundle_atomically(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin);

        $this->get('/dataset')->assertOk()
            ->assertSee('Impor Data Akademik Sekolah Sekaligus')
            ->assertDontSee('Mode Kerjasama')
            ->assertDontSee('kerja sama dengan sekolah baru');

        $this->get('/dataset/templates/master_bundle?format=xlsx')->assertOk();

        $sheets = [
            [
                'name' => '01_Tahun_Ajaran',
                'headers' => ['nama_tahun_ajaran', 'tanggal_mulai', 'tanggal_selesai'],
                'rows' => [['2027/2028', '2027-07-12', '2028-06-20']],
            ],
            [
                'name' => '02_Jurusan_Tingkat',
                'headers' => ['kode_jurusan', 'nama_jurusan', 'nomor_tingkat', 'nama_tingkat'],
                'rows' => [['RPL', 'Rekayasa Perangkat Lunak', '10', 'Kelas X']],
            ],
            [
                'name' => '03_Rombel',
                'headers' => ['tahun_ajaran', 'semester', 'kode_jurusan', 'nomor_tingkat', 'nama_rombel'],
                'rows' => [['2027/2028', 'Ganjil', 'RPL', '10', 'X-RPL-1']],
            ],
            [
                'name' => '04_Data_Guru',
                'headers' => ['nip', 'nama_lengkap', 'email', 'status_akun'],
                'rows' => [['199001012020121009', 'Dimas Prakoso, S.Kom.', 'dimas.rpl@sekolah.sch.id', 'active']],
            ],
            [
                'name' => '05_Mata_Pelajaran',
                'headers' => ['kode_mapel', 'nama_mapel', 'jp_total', 'jp_teori', 'jp_praktik', 'kode_jurusan', 'nomor_tingkat', 'semester', 'prasyarat', 'deskripsi', 'status'],
                'rows' => [['PD-10', 'Pemrograman Dasar X', '4', '2', '2', 'RPL', '10', '1', '', 'Algoritma dan logika pemrograman.', 'active']],
            ],
            [
                'name' => '06_Kelas_dan_Jadwal',
                'headers' => ['nip_guru', 'kode_mapel', 'nama_rombel', 'tahun_ajaran', 'semester', 'hari', 'jam_mulai', 'jam_selesai', 'ruang_kelas'],
                'rows' => [['199001012020121009', 'PD-10', 'X-RPL-1', '2027/2028', '1', 'Senin', '07:30', '09:00', 'Lab RPL 1']],
            ],
            [
                'name' => '07_Data_Siswa',
                'headers' => ['nis', 'nama_lengkap', 'email', 'tahun_ajaran', 'semester', 'kode_jurusan', 'nomor_tingkat', 'nama_rombel', 'status_akun'],
                'rows' => [
                    ['20271001', 'Fajar Nugroho', 'fajar.nugroho@siswa.sch.id', '2027/2028', '1', 'RPL', '10', 'X-RPL-1', 'active'],
                    ['20271002', 'Alya Zahra', 'alya.zahra@siswa.sch.id', '2027/2028', '1', 'RPL', '10', 'X-RPL-1', 'active'],
                ],
            ],
        ];

        $file = $this->makeUploadedXlsx($sheets, 'data_sekolah_terpadu.xlsx');
        $this->post('/dataset/preview', ['module' => 'master_bundle', 'file' => $file])
            ->assertRedirect('/dataset/preview');

        // Dry-run must NOT have persisted records before confirmation
        $this->assertDatabaseMissing('programs', ['code' => 'RPL']);
        $this->assertDatabaseMissing('students', ['student_number' => '20271001']);

        $previewPage = $this->get('/dataset/preview')->assertOk()
            ->assertSee('Seluruh Baris Tervalidasi dan Siap Disimpan')
            ->assertSee('07_Data_Siswa');

        $token = session('dataset_preview.token');
        $this->assertNotEmpty($token);

        $this->post('/dataset/commit', ['preview_token' => $token])
            ->assertRedirect('/dataset')
            ->assertSessionHas('success');

        // Verify all records are directly persisted & auto-enrolled in the database
        $this->assertDatabaseHas('academic_years', ['name' => '2027/2028']);
        $program = Program::where('code', 'RPL')->firstOrFail();
        $teacher = Teacher::where('employee_number', '199001012020121009')->firstOrFail();
        $student1 = Student::where('student_number', '20271001')->firstOrFail();
        $student2 = Student::where('student_number', '20271002')->firstOrFail();

        $newClass = TeacherAssignment::where('teacher_id', $teacher->id)->firstOrFail();
        $this->assertTrue(Enrollment::where('teacher_assignment_id', $newClass->id)->where('student_id', $student1->id)->exists());
        $this->assertTrue(Enrollment::where('teacher_assignment_id', $newClass->id)->where('student_id', $student2->id)->exists());
        $this->assertDatabaseHas('class_schedules', ['teacher_assignment_id' => $newClass->id, 'room' => 'Lab RPL 1', 'day' => 1]);
        $this->assertDatabaseHas('dataset_audit_logs', ['user_id' => $admin->id, 'action' => 'import', 'module' => 'master_bundle']);
        $this->assertNotNull($program->id);
    }

    public function test_teacher_can_import_grades_attendance_lessons_and_quiz_questions_via_indonesian_semicolon_csv(): void
    {
        $teacher = $this->user('teacher');
        $this->actingAs($teacher);

        $classroom = Access::classes($teacher)->with(['block', 'subject'])->firstOrFail();
        $rombel = $classroom->block->name;
        $mapel = $classroom->subject->code;
        $enrolledStudent = Enrollment::with('student')->where('teacher_assignment_id', $classroom->id)->firstOrFail()->student;
        $assignment = Assignment::where('teacher_assignment_id', $classroom->id)->firstOrFail();

        // 1. Semicolon-separated CSV grade import
        $csvGrades = "nama_rombel;kode_mapel;judul_tugas;nis;nama_siswa;skor;umpan_balik_guru;status_penilaian\r\n"
            ."{$rombel};{$mapel};{$assignment->title};{$enrolledStudent->student_number};Siswa;96;Sangat memuaskan;graded\r\n";

        $file = UploadedFile::fake()->createWithContent('nilai_kelas.csv', $csvGrades);
        $this->post('/dataset/preview', ['module' => 'grades', 'class_id' => $classroom->id, 'file' => $file])
            ->assertRedirect('/dataset/preview');

        $token = session('dataset_preview.token');
        $this->post('/dataset/commit', ['preview_token' => $token])
            ->assertRedirect('/dataset?class_id='.$classroom->id);

        $this->assertDatabaseHas('grades', [
            'teacher_assignment_id' => $classroom->id,
            'student_id' => $enrolledStudent->id,
            'source_type' => 'assignment',
            'source_id' => $assignment->id,
            'score' => 96,
        ]);

        // 2. Attendance import within 7-day window succeeds, while >7 days ago is rejected for teacher
        $oldDate = today()->subDays(10)->format('Y-m-d');
        $oldCsv = "nama_rombel;kode_mapel;tanggal;jam_mulai;jam_selesai;nis;status_kehadiran\r\n"
            ."{$rombel};{$mapel};{$oldDate};08:00;09:00;{$enrolledStudent->student_number};Hadir\r\n";
        $this->post('/dataset/preview', [
            'module' => 'attendance',
            'class_id' => $classroom->id,
            'file' => UploadedFile::fake()->createWithContent('absen_lama.csv', $oldCsv),
        ])->assertRedirect('/dataset/preview');

        $this->get('/dataset/preview')->assertOk()->assertSee('maksimal 7 hari ke belakang');
        $this->post('/dataset/commit', ['preview_token' => session('dataset_preview.token')])
            ->assertRedirect('/dataset/preview');

        $todayStr = today()->format('Y-m-d');
        $validAttCsv = "nama_rombel;kode_mapel;tanggal;jam_mulai;jam_selesai;nis;status_kehadiran;menit_terlambat;keterangan\r\n"
            ."{$rombel};{$mapel};{$todayStr};10:00;11:00;{$enrolledStudent->student_number};Terlambat;12;Hujan deras\r\n";
        $this->post('/dataset/preview', [
            'module' => 'attendance',
            'class_id' => $classroom->id,
            'file' => UploadedFile::fake()->createWithContent('absen_hari_ini.csv', $validAttCsv),
        ])->assertRedirect('/dataset/preview');
        $this->post('/dataset/commit', ['preview_token' => session('dataset_preview.token')])
            ->assertRedirect('/dataset?class_id='.$classroom->id);

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $enrolledStudent->id,
            'status' => 'late',
            'minutes_late' => 12,
            'remarks' => 'Hujan deras',
        ]);

        // 3. Quiz question bank import
        $quizCsv = "nama_rombel,kode_mapel,judul_kuis,durasi_menit,pertanyaan,opsi_a,opsi_b,opsi_c,opsi_d,kunci_jawaban,poin,status_kuis\r\n"
            ."{$rombel},{$mapel},Kuis Bab Baru,25,Apa fungsi struktur percabangan?,Mengulang kode,Memilih alur berdasar kondisi,Menghapus memori,Menutup aplikasi,B,10,published\r\n";
        $this->post('/dataset/preview', [
            'module' => 'quiz_questions',
            'class_id' => $classroom->id,
            'file' => UploadedFile::fake()->createWithContent('soal_kuis.csv', $quizCsv),
        ])->assertRedirect('/dataset/preview');
        $this->post('/dataset/commit', ['preview_token' => session('dataset_preview.token')])
            ->assertRedirect('/dataset?class_id='.$classroom->id);

        $this->assertDatabaseHas('quizzes', ['teacher_assignment_id' => $classroom->id, 'title' => 'Kuis Bab Baru']);
        $this->assertDatabaseHas('quiz_questions', ['question' => 'Apa fungsi struktur percabangan?', 'correct_answer' => 'b']);

        // 4. Exports in XLSX, CSV, PDF log audit records
        $this->get('/dataset/export/grades?format=xlsx&class_id='.$classroom->id)->assertOk();
        $this->get('/dataset/export/attendance?format=csv&class_id='.$classroom->id)->assertOk();
        $this->assertTrue(DatasetAuditLog::where('user_id', $teacher->id)->where('action', 'export')->exists());
    }

    public function test_schedule_conflict_and_spreadsheet_formula_injection_are_protected(): void
    {
        $this->assertSame("'=CMD|' /C calc'!A0", SpreadsheetCodec::safeCellValue("=CMD|' /C calc'!A0"));
        $this->assertSame('95', SpreadsheetCodec::safeCellValue('95'));

        $admin = $this->user('admin');
        $this->actingAs($admin);

        // Existing seeded schedule on 3J PF102 is day 1 (Senin) 08:00-09:00 in IT Lab 1.
        // Attempting to schedule NET102 on 3J at overlapping Senin 08:30-09:30 must report conflict!
        $conflictCsv = "nama_rombel,kode_mapel,hari,jam_mulai,jam_selesai,ruang_kelas,tahun_ajaran,semester\r\n"
            ."3J,NET102,Senin,08:30,09:30,IT Lab 1,2026–2027,1\r\n";

        $this->post('/dataset/preview', [
            'module' => 'schedules',
            'file' => UploadedFile::fake()->createWithContent('jadwal_bentrok.csv', $conflictCsv),
        ])->assertRedirect('/dataset/preview');

        $this->get('/dataset/preview')
            ->assertOk()
            ->assertSee('Bentrok jadwal')
            ->assertDontSee('Konfirmasi &amp; Simpan ke Basis Data', false);
    }
}
