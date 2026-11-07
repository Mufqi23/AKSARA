# AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)

Sistem Manajemen Pembelajaran (*E-Learning*) dan Portal Informasi Akademik Sekolah berbasis rombongan belajar (*rombel*), multi-peran, dan terintegrasi penuh dengan **AI Deteksi Dini Siswa Berisiko (*Academic Early Warning System*)**. Dibangun dengan framework **Laravel 12, PHP 8.2+, MySQL, Blade, dan Vanilla CSS + Bootstrap**.

Proyek **AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)** dikembangkan dengan arsitektur bersih (*clean MVC*), *Service Layer* modular, standar keamanan tinggi (*Two-Factor Authentication / 2FA* via email OTP, *signed URLs*, *secure & encrypted session*), serta telah terverifikasi dengan **146 kasus uji otomatis (3.253 assertions - 100% PASS)**.

---

## Rincian Kontribusi & Modul Utama

Berikut adalah modul dan fitur inti yang tersedia pada sistem sekolah **AKSARA**:

### 1. AI Deteksi Dini Siswa Berisiko (*Academic Early Warning System*)
- **Mesin Penilaian Risiko Multi-Faktor (`App\Services\Ai\EarlyWarningCalculator`)**: Menganalisis secara *real-time* kehadiran harian siswa (bobot 35%), pengumpulan tugas tepat waktu (bobot 30%), rata-rata nilai akademik mata pelajaran (bobot 25%), dan keaktifan pengerjaan evaluasi/kuis (bobot 10%).
- **Klasifikasi Status Risiko Otomatis**: Mengelompokkan kondisi belajar siswa menjadi **Aman** (*Low Risk*, skor 0–34), **Perlu Perhatian** (*Medium Risk*, skor 35–64), dan **Berisiko Tinggi** (*High Risk*, skor 65–100) disertai aturan eskalasi otomatis bila kehadiran di bawah 65% atau terdapat banyak tugas melewati tenggat.
- **Narasi Diagnostik & Rekomendasi Pembimbingan (`App\Services\Ai\EarlyWarningNarrative`)**: Menghasilkan ringkasan analisis bahasa alami yang santun dan konstruktif untuk Guru Pengajar, Wali Kelas, Administrator Sekolah, maupun umpan balik mandiri bagi Siswa. Lihat dokumentasi lengkap di [docs/AI_EARLY_WARNING_SYSTEM.md](docs/AI_EARLY_WARNING_SYSTEM.md).

### 2. Keamanan Siber & Autentikasi Modern (*Security Hardening*)
- **Autentikasi Dua Faktor (*Two-Factor Authentication / 2FA*)**: Kode OTP 6 digit dikirim ke email pengguna (`App\Notifications\TwoFactorCode`), disimpan dalam bentuk *hash*, berlaku 10 menit, dapat dikirim ulang (`POST /two-factor-challenge/resend`, maks. 3/menit), dan dapat diaktifkan/dinonaktifkan dari halaman profil.
- **Proteksi Cookie & Sesi**: `SESSION_SECURE_COOKIE=true` (HTTPS), `SESSION_ENCRYPT=true` (isi sesi terenkripsi), `SESSION_HTTP_ONLY=true`, dan `SameSite=lax`.
- **Aktivasi Akun Berbasis Signed URL**: Undangan aktivasi akun sekolah terenkripsi dengan *signed URL* bertenggat 60 menit untuk mencegah *account hijacking* dan registrasi liar.
- **Rate Limiting & Throttle**: Pembatasan ketat pada endpoint login (15/menit), lupa kata sandi (3/menit), reset kata sandi (6/menit), dan verifikasi 2FA (6/menit).
- **Akses Berkas Privat (*Private Disk Streaming*)**: Seluruh bahan ajar mata pelajaran dan berkas tugas siswa disimpan di disk lokal privat, diakses melalui controller khusus dengan validasi perizinan kelas.
- **CSRF & Header Keamanan**: Dilengkapi proteksi token CSRF otomatis dan pencegahan eksploitasi MIME tipe (*nosniff*).

### 3. Arsitektur Kode Modular & Service Layer (*MVC Clean Architecture*)
- **Tanpa Route Closure**: Seluruh rute web mengarah ke method controller; `routes/console.php` hanya berisi penjadwalan. Perintah Artisan berbentuk class di `app/Console/Commands/` (`CreateAdminCommand`, `SendDeadlineRemindersCommand`, `ExpireQuizzesCommand`, `ImportCurriculum`).
- **Controller Khusus per Sumber Daya**:
  - Data master admin sekolah: `app/Http/Controllers/Admin/` berisi `UserController`, `AcademicYearController`, `ProgramController`, `YearLevelController`, `BlockController`, `SubjectController`, `StudentController`, `TeacherController`, `TeacherAssignmentController`, `EnrollmentController`, `ScheduleController` (mewarisi `Admin\ResourceController`). Contoh URL: `/admin/manage/programs`.
  - Konten kelas: `LessonController`, `MaterialController`, `AssignmentController`, `QuizController` dengan URL eksplisit seperti `/classes/{classroom}/content/lessons`.
  - AI Peringatan Dini: `EarlyWarningController` (`/early-warning` dan `/early-warning/students/{student}`).
  - Laporan sekolah: `/reports/attendance`, `/reports/grades`, `/reports/students`, `/reports/enrollments`.
  - Unduhan berkas: `/files/materials/{id}`, `/files/assignments/{id}`, `/files/submissions/{id}`.
- **Pemisahan Logika Bisnis (`app/Services/`)**:
  - `Ai\EarlyWarningService`, `Ai\EarlyWarningCalculator`, `Ai\EarlyWarningNarrative`: Mesin analitik AI peringatan dini akademik siswa.
  - `AcademicSetup` (+ modul di `Setup/`): Menangani wizard 5 langkah inisialisasi tahun ajaran, validasi struktur bertingkat, dan pembuatan entitas transaksional.
  - `CurriculumImport` (+ modul di `Curriculum/`): Parser CSV kurikulum sekolah dengan validasi prasyarat (*prerequisites*), beban JP teori/praktik, dan pencegahan duplikasi kode mata pelajaran.
  - `StudentCsvImport`: Parser massal data siswa berbasis CSV dengan dukungan UTF-8 BOM, pencocokan rombel/jurusan, dan verifikasi akun lama/baru.
  - `AttendanceService`: Penghitungan persentase kehadiran siswa dengan pengecualian izin/sakit (*excused absences*).
  - `Catalog`: Validasi relasi dan definisi dinamis formulir tata usaha akademik sekolah.

### 4. Modul Akademik Sekolah & Deteksi Bentrok Jadwal
- **Wizard Setup Tahun Ajaran Multi-Jurusan**: Pembuatan tahun ajaran, jurusan, tingkat kelas (Kelas X/XI/XII), rombongan belajar (*rombel*), penugasan guru mengajar, dan jadwal pelajaran secara sekaligus.
- **Deteksi Bentrok Jadwal Otomatis**: Pencegahan bentrok jam mengajar guru maupun bentrok penggunaan ruang kelas secara *real-time*.
- **Pendaftaran Kelas Otomatis (*Auto-Enrollment*)**: Penempatan siswa ke dalam suatu rombel otomatis mendaftarkan siswa ke seluruh mata pelajaran pada rombel tersebut.

### 5. Modul Kegiatan Belajar Mengajar Interaktif
- **Manajemen Materi & Pertemuan**: Publikasi topik pertemuan terstruktur dan unggahan bahan ajar pelajaran.
- **Pengumpulan Tugas Multi-Versi**: Siswa dapat mengunggah revisi tugas dengan riwayat pengumpulan lengkap (*versioned submissions*). Guru dapat memberi nilai dan catatan evaluasi perbaikan.
- **Kuis Pilihan Ganda Ber-timer**: Evaluasi interaktif dengan batas waktu pengerjaan, pengacakan soal, dan *auto-submit* saat waktu habis.
- **Buku Nilai (*Gradebook*)**: Matriks rekapitulasi nilai tugas dan evaluasi siswa per kelas mata pelajaran secara transparan.

### 6. Presensi Harian & Audit Kehadiran Sekolah
- **Sesi Presensi Digital**: Pencatatan status Hadir, Terlambat (*configurable threshold*), Izin/Sakit, dan Alpa pada setiap jam pelajaran.
- **Alur Permohonan Izin / Sakit**: Siswa dapat mengajukan keterangan ketidakhadiran yang ditinjau langsung oleh guru pengajar.
- **Audit Log Kehadiran**: Setiap koreksi catatan presensi diaudit lengkap dengan stempel waktu dan alasan perubahan dari administrator sekolah.

### 7. Desain UI/UX Sekolah & Lokalisasi
- **Halaman Depan (*Landing Page*) Sekolah**: Antarmuka bersih bertema *Neumorphic Academic* dengan indikator ringkas sekolah, pengenalan fitur kegiatan belajar mengajar, serta dukungan mode terang/gelap yang tersinkronisasi dengan portal dalam. Lihat [docs/LANDING_PAGE_REDESIGN.md](docs/LANDING_PAGE_REDESIGN.md).
- ** Struktur Aset CSS & JS Terpisah Rapi**: Seluruh stylesheet dipecah modular ke dalam `public/css/app/*.css` dan `public/css/landing/*.css`, serta skrip interaktif di `public/js/modules/*.js`.
- **Lokalisasi Bahasa Indonesia & WIB**: Seluruh antarmuka, notifikasi, dan pesan validasi (`lang/id/`) berbahasa Indonesia dengan zona waktu `Asia/Jakarta` (WIB).

---

## Kebutuhan Sistem

- **PHP 8.2+** (Ekstensi: `pdo_mysql`, `pdo_sqlite`, `mbstring`, `fileinfo`, `dom`, `curl`, `openssl`, `zip`)
- **Composer 2.x**
- **MySQL 8.0+**
- Tidak memerlukan kompilasi Node/NPM/Webpack/Vite untuk menjalankan aplikasi (aset Bootstrap, CSS, dan JS telah tersedia secara mandiri di `public/`).

---

## Panduan Instalasi Lokal

1. **Clone & Masuk ke Direktori Proyek**:
   ```bash
   git clone <url-repo>
   cd aksara
   ```

2. **Pasang Dependensi**:
   ```bash
   composer install
   ```

3. **Salin Konfigurasi Lingkungan**:
   ```powershell
   copy .env.example .env
   ```
   *(Gunakan `cp .env.example .env` pada Linux/macOS)*

4. **Generate Application Key**:
   ```bash
   php artisan key:generate
   ```

5. **Konfigurasi Database di `.env`**:
   Buat database MySQL baru dengan nama `aksara` (karakter `utf8mb4`), lalu sesuaikan konfigurasi di `.env`:
   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=aksara
   DB_USERNAME=root
   DB_PASSWORD=
   APP_TIMEZONE=Asia/Jakarta
   APP_LOCALE=id
   ```

6. **Migrasi & Seed Data Demo Sekolah**:
   ```bash
   php artisan migrate --seed
   ```

7. **Jalankan Server Lokal**:
   ```bash
   php artisan serve
   ```
   Buka peramban di **http://localhost:8000**.

---

## Kredensial Akun Demo Sekolah

Seluruh akun demo menggunakan kata sandi bawaan: **`CampusDemo!2026`**

| Peran Sekolah | Alamat Email | Keterangan |
| :--- | :--- | :--- |
| **Administrator Sekolah** | `admin@example.com` | Akses penuh setup tahun ajaran, data master sekolah, dan pelaporan |
| **Guru Pengajar** | `teacher@example.com` | Pengajar kelas, input bahan ajar, penilaian, presensi harian, & AI peringatan dini |
| **Siswa** | `student@example.com` | Mengakses materi pelajaran, tugas, jadwal mingguan, dan rekap nilai |

*Akun guru pendukung:* `teacher2@example.com`, `teacher3@example.com`  
*Akun siswa pendukung:* `student2@example.com` sampai `student8@example.com`

---

## Pengujian Otomatis (*Automated Testing*)

Aplikasi dilengkapi rangkaian pengujian otomatis menyeluruh menggunakan PHPUnit:

```bash
php artisan test
```

**Hasil Pengujian:**
- Total File Uji: **13 Test Suites** (termasuk `SchoolDatasetHubTest`, `EarlyWarningSystemTest`, `TwoFactorAuthTest`, `TeacherWorkflowTest`, `MultiProgramSetupTest`, dll.)
- Total Test Cases: **146 Kasus Uji**
- Status: **100% Passed (Green)**

---

## Panduan Pengemasan Bersih (*Packaging*)

Untuk memastikan berkas sensitif (`.env`), direktori besar (`vendor/`), berkas cache (`.phpunit.result.cache`), dan folder `.git/` tidak ikut terbawa ke dalam arsip pengumpulan:

### Cara 1: Menggunakan File Batch (Windows)
Klik dua kali berkas **`pack-submission.bat`** di folder utama proyek.

### Cara 2: Menggunakan PowerShell
```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\pack-submission.ps1
```

Script ini menghasilkan berkas **`aksara-submission.zip`** yang bersih, ringan, dan siap dikumpulkan.

---

## Dokumentasi Lengkap (`docs/`)

- [Arsitektur & Peta Aplikasi](docs/ARCHITECTURE.md)
- [Pusat Ekspor & Impor Dataset Sekolah Terpadu](docs/SCHOOL_DATASET_IMPORT_EXPORT.md)
- [AI Deteksi Dini Siswa Berisiko (Early Warning System)](docs/AI_EARLY_WARNING_SYSTEM.md)
- [Desain Halaman Utama Sekolah (Landing Page)](docs/LANDING_PAGE_REDESIGN.md)
- [Tiga Modul Inti Sekolah](docs/CORE-MODULES.md)
- [Peningkatan Alur Kerja Sekolah](docs/WORKFLOW_IMPROVEMENTS.md)
- [Setup Multi-Jurusan dalam Satu Tahun Ajaran](docs/MULTI_PROGRAM_SETUP.md)
- [Sistem Impor Kurikulum Sekolah](docs/CURRICULUM.md)
- [Panduan Pengujian Otomatis](docs/TESTING.md)
- [Panduan Deployment Server](docs/DEPLOYMENT.md)
- [Panduan Deployment Vercel](docs/VERCEL.md)

---

## Lisensi & Kredit

Proyek ini dirilis di bawah lisensi [MIT License](LICENSE).  
Copyright (c) 2026 **AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)**.
