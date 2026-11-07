# Peta Aplikasi AKSARA

**AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)** dibangun dengan Laravel 12, Blade yang dirender di sisi server, aset Bootstrap 5.3.8 lokal, CSS & JS modular, MySQL, Eloquent, sesi terenkripsi Laravel, notifikasi, mail, validasi, dan penyimpanan berkas privat. Zona waktu bawaan adalah `Asia/Jakarta` dan locale bawaan `id`.

## Model Akademik Sekolah

```
academic_years + programs + year_levels + semester → blocks (rombel) → students (siswa)
teacher (guru) + block (rombel) + subject (mata pelajaran) → teacher_assignments (kelas) → enrollments
```

- `TeacherAssignment` adalah agregat **kelas mata pelajaran** yang dipakai di seluruh antarmuka portal sekolah.
- Menempatkan siswa ke sebuah rombongan belajar (*rombel* / `blocks`) otomatis mendaftarkannya ke seluruh kelas mata pelajaran pada rombel tersebut. Membuat kelas baru otomatis mendaftarkan siswa yang sudah tergabung di rombel itu. Administrator sekolah tetap dapat mengelola pendaftaran (*enrollment*) secara eksplisit.
- Jurusan (`programs`) dan tingkat kelas siswa (`year_levels`) dinormalisasi melalui relasi rombel.
- Pertemuan pelajaran, bahan ajar, tugas, kuis evaluasi, jadwal pelajaran, nilai, dan sesi presensi harian merujuk ke sebuah kelas. Rekaman presensi merujuk ke pasangan sesi/siswa dengan *unique constraint* di basis data. Versi pengumpulan tugas dan nomor percobaan kuis memakai *compound unique constraint*.
- *Foreign key* MySQL melindungi riwayat akademik siswa; akun pengguna mendukung *soft delete* dan penangguhan (suspensi).

## Struktur Kode Modular

### Routing

- `routes/web.php` hanya berisi rute ke *controller* — tidak ada *route closure*. Berkas `routes/teacher-workflows.php` dan `routes/academic-setup.php` dimuat di dalam grup rute terproteksi.
- `routes/console.php` hanya berisi entri `Schedule::command`.

### Data Master Tata Usaha / Admin Sekolah

Setiap sumber daya administrasi sekolah memiliki *controller* khusus di `app/Http/Controllers/Admin/`:

| Controller | URL | Entitas Sekolah |
| --- | --- | --- |
| `UserController` | `/admin/manage/users` | Akun Pengguna |
| `AcademicYearController` | `/admin/manage/academic-years` | Tahun Ajaran |
| `ProgramController` | `/admin/manage/programs` | Jurusan |
| `YearLevelController` | `/admin/manage/year-levels` | Tingkat Kelas |
| `BlockController` | `/admin/manage/blocks` | Kelas & Rombel |
| `SubjectController` | `/admin/manage/subjects` | Mata Pelajaran |
| `StudentController` | `/admin/manage/students` | Data Siswa |
| `TeacherController` | `/admin/manage/teachers` | Data Guru |
| `TeacherAssignmentController` | `/admin/manage/teacher-assignments` | Kelas & Penugasan Guru |
| `EnrollmentController` | `/admin/manage/enrollments` | Pendaftaran Kelas Siswa |
| `ScheduleController` | `/admin/manage/schedules` | Jadwal Pelajaran |

Masing-masing menyediakan `index`, `create`, `{id}/edit`, `store`, `update`, dan `destroy`. Semuanya mewarisi kelas abstrak `Admin\ResourceController`, yang mendelegasikan validasi dan persistensi bersama ke `AdminController`, `App\Services\Catalog`, dan `App\Http\Requests\CatalogRequest`.

### Konten Kelas Pembelajaran

`LessonController`, `MaterialController`, `AssignmentController`, dan `QuizController` memiliki rute eksplisit:

- `GET /classes/{classroom}/content/lessons/create`
- `GET /classes/{classroom}/content/lessons/{id}/edit`
- `POST /classes/{classroom}/content/lessons`
- `PUT|DELETE /classes/{classroom}/content/lessons/{id}`

Pola yang sama berlaku untuk `materials`, `assignments`, dan `quizzes`. Logika bersama berada di `ClassController` dan `App\Services\ClassContent`.

### Modul AI Peringatan Dini (`app/Services/Ai/`)

- `EarlyWarningController` menangani `/early-warning` dan `/early-warning/students/{student}`.
- `EarlyWarningService` mengoordinasikan kueri teragregasi bebas *N+1*, `EarlyWarningCalculator` menghitung skor risiko (0–100), dan `EarlyWarningNarrative` menyusun narasi diagnostik serta rekomendasi pembimbingan bagi guru dan siswa.

### Perintah Artisan

Semua perintah berupa kelas di `app/Console/Commands/`:

| Kelas | Perintah | Fungsi |
| --- | --- | --- |
| `CreateAdminCommand` | `lms:create-admin` | Membuat akun administrator sekolah secara interaktif tanpa menyimpan kata sandi di riwayat shell |
| `SendDeadlineRemindersCommand` | `lms:deadlines` | Mengirim pengingat tenggat tugas siswa (dijadwalkan per jam) |
| `ExpireQuizzesCommand` | `lms:expire-quizzes` | Menyelesaikan dan menilai percobaan kuis yang kedaluwarsa (dijadwalkan per menit) |
| `ImportCurriculum` | `lms:import-curriculum` | Memvalidasi dan mengimpor struktur kurikulum mata pelajaran; lihat [CURRICULUM.md](CURRICULUM.md) |

### Layanan (`app/Services`)

`AcademicSetup`, `Access`, `Ai\EarlyWarningService`, `Ai\EarlyWarningCalculator`, `Ai\EarlyWarningNarrative`, `AssignmentGrading`, `AttendanceService`, `Catalog`, `ClassContent`, `CurriculumImport`, `DashboardDataService`, `Files`, `ReportExport`, `ScheduleConflicts`, dan `StudentCsvImport`.

## Otorisasi

- Semua rute portal mewajibkan autentikasi, status akun aktif, dan email terverifikasi. Rute administratif juga mewajibkan peran administrator sekolah (`admin`).
- `Access::classes` membatasi akses baca ke profil guru pengajar atau pendaftaran siswa yang sah. Setiap perubahan pada kelas memeriksa ulang kelas dan peran pengguna.
- Pada modul AI Peringatan Dini, guru hanya melihat siswa yang terdaftar di kelas ajarnya, siswa hanya dapat melihat profil evaluasinya sendiri, dan administrator sekolah memantau seluruh sekolah.
- Unduhan pengumpulan tugas memeriksa kepemilikan siswa; percobaan kuis memeriksa kepemilikan; filter laporan dijalankan di dalam kueri yang sudah terotorisasi. *Gate* `manage-class` terdaftar untuk UI dan ekstensi.

## Keamanan

| Aspek | Perilaku |
| --- | --- |
| Autentikasi dua faktor (2FA) | Opsional per pengguna (diaktifkan di profil). Kode OTP 6 digit dikirim lewat email melalui `App\Notifications\TwoFactorCode`, berlaku 10 menit, dan disimpan dalam bentuk hash di basis data. |
| Pembatasan laju | Login 15/menit; verifikasi OTP 6/menit; kirim ulang OTP melalui `POST /two-factor-challenge/resend` 3/menit. |
| Sesi | `SESSION_ENCRYPT` bawaan `true`, `SESSION_SECURE_COOKIE` `true`, cookie `http_only`, `same_site` `lax`. |
| Bahasa | Terjemahan Bahasa Indonesia di `lang/id` (`auth`, `passwords`, `pagination`, `validation`). |

## Aturan Penilaian Sekolah

- Topik pertemuan, bahan ajar, dan tugas berstatus draf atau terbit. Siswa hanya melihat konten yang sudah diterbitkan oleh guru.
- Semua versi pengumpulan tugas siswa disimpan. Penilaian guru memvalidasi batas poin, mencatat umpan balik perbaikan, dan memperbarui buku nilai dari versi terakhir yang dinilai.
- Kuis mendukung soal pilihan ganda, jumlah percobaan yang dikonfigurasi, rentang ketersediaan, dan stempel waktu kedaluwarsa di sisi server. Soal hanya dapat diubah selama kuis masih draf dan belum ada percobaan.
- Persentase akhir di buku nilai kelas menjumlahkan skor terhadap seluruh poin penilaian yang tercantum; tugas yang belum dikumpulkan bernilai nol dalam perhitungan total kelas.

## Aturan Presensi Harian

- Ambang keterlambatan bawaan 15 menit dan dapat diatur untuk sesi baru. Setiap sesi menyimpan ambangnya sendiri sehingga perubahan pengaturan tidak mengubah catatan presensi lama. Menit kedatangan menentukan status Hadir atau Terlambat.
- Guru dapat mencatat/mengubah presensi hingga tujuh hari kalender setelah sesi pelajaran berlangsung; rekaman yang lebih lama hanya dapat dikoreksi oleh administrator sekolah dengan alasan wajib.
- Setiap pembuatan atau koreksi rekaman menyimpan JSON sebelum/sesudah, pelaku, dan alasan di log audit.
- Siswa dapat mengajukan permohonan izin/sakit untuk rekaman ketidakhadiran. Guru atau admin yang berwenang menyetujui atau menolaknya. Rekaman berizin dikecualikan dari penyebut tingkat kehadiran.

## Laporan dan Ekspor Sekolah

| Rute | Method `ReportController` |
| --- | --- |
| `/reports/attendance` | `attendance` |
| `/reports/grades` | `grades` |
| `/reports/students` | `students` |
| `/reports/enrollments` | `enrollments` |
| `/classes/{classroom}/gradebook` | `gradebook` |

- Laporan presensi mendukung filter tahun ajaran, semester, jurusan, tingkat kelas, rombel, mata pelajaran, siswa, status, dan rentang tanggal, serta filter guru untuk admin.
- PDF dibuat menggunakan Dompdf dengan akses sumber daya jarak jauh dinonaktifkan. Excel berupa workbook `.xlsx` OpenXML yang menulis sel sebagai string eksplisit agar aman dari injeksi formula.

## Rute Utama

| Area | Rute |
| --- | --- |
| Publik & Autentikasi | `/`, `/login`, `/two-factor-challenge`, `/two-factor-challenge/resend`, `/logout`, `/activate/{user}`, `/verify-email`, `/forgot-password`, `/reset-password/{token}` |
| Portal Sekolah | `/dashboard`, `/classes`, `/classes/{classroom}`, `/schedule`, `/search`, `/profile`, `/profile/two-factor`, `/announcements`, `/notifications` |
| Pusat Dataset Sekolah | `/dataset`, `/dataset/preview`, `/dataset/commit`, `/dataset/templates/{module}`, `/dataset/export/{module}` |
| AI Peringatan Dini | `/early-warning` |
| Konten Kelas | `/classes/{classroom}/content/{lessons,materials,assignments,quizzes}` |
| Tugas & Nilai | `/assignments/{assignment}`, `/assignments/{assignment}/submit`, `/assignments/{assignment}/grades`, `/submissions/{submission}/grade` |
| Unduhan Berkas | `/files/materials/{id}`, `/files/assignments/{id}`, `/files/submissions/{id}` |
| Evaluasi Kuis | `/quizzes/{quiz}`, `/quizzes/{quiz}/questions`, `/quizzes/{quiz}/start`, `/attempts/{attempt}` |
| Presensi Harian | `/classes/{classroom}/attendance`, `/attendance/sessions/{session}`, `/attendance/sessions/{session}/bulk-records`, `/attendance/{record}/excuse`, `/excuses/{excuse}` |
| Laporan Sekolah | `/reports/attendance`, `/reports/grades`, `/reports/students`, `/reports/enrollments`, `/classes/{classroom}/gradebook` |
| Administrasi | `/admin/manage/{sumber-daya}`, `/admin/settings`, `/admin/users/{user}/invite`, `/admin/setup` |
