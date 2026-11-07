# Peningkatan Alur Kerja & Modul Sekolah AKSARA

Dokumen ini merangkum penyempurnaan alur kerja, struktur kode modular, serta fitur akademik pada **AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)** yang dibangun menggunakan Laravel 12, Blade, dan Bootstrap.

---

## 1. Arsitektur Terstruktur, Keamanan, & AI Sekolah

- **Ekosistem Terpadu Sekolah**:
  1. *Manajemen Akademik Sekolah*: Penyiapan tahun ajaran, jurusan, tingkat kelas (X/XI/XII), rombongan belajar (*rombel*), mata pelajaran, penugasan guru pengajar, dan jadwal pelajaran.
  2. *Kegiatan Belajar Mengajar (E-Learning)*: Pertemuan terstruktur, bahan ajar mata pelajaran, penugasan multi-versi, evaluasi kuis berbatas waktu, dan buku nilai (*gradebook*).
  3. *Presensi Harian & AI Pemantauan Siswa*: Pencatatan kehadiran harian siswa dengan alur permohonan izin/sakit, audit log presensi, pelaporan rapor/rekap, serta **AI Deteksi Dini Siswa Berisiko (*Academic Early Warning System*)**.
- **Struktur Berkas & Controller Modular**:
  - Seluruh rute data master tata usaha dipisah ke dalam controller mandiri di `app/Http/Controllers/Admin/` (`UserController`, `AcademicYearController`, `ProgramController`, `YearLevelController`, `BlockController`, `SubjectController`, `StudentController`, `TeacherController`, `TeacherAssignmentController`, `EnrollmentController`, `ScheduleController`).
  - Konten kelas dikelola oleh `LessonController`, `MaterialController`, `AssignmentController`, dan `QuizController`, sedangkan analitik peringatan dini dikelola oleh `EarlyWarningController` beserta layanan modular di `app/Services/Ai/`.
  - Seluruh stylesheet dan skrip antarmuka dipecah rapi per fungsi di `public/css/app/*.css`, `public/css/landing/*.css`, dan `public/js/modules/*.js`.
- **Keamanan Siber & Autentikasi 2FA**:
  - Dukungan Autentikasi Dua Faktor (2FA) via kode OTP 6 digit ke email pengguna (`App\Notifications\TwoFactorCode`) dengan masa berlaku 10 menit dan *rate limiting*.
  - Sesi terenkripsi secara bawaan (`SESSION_ENCRYPT=true`) dengan cookie aman (`SESSION_SECURE_COOKIE=true`, `http_only`).
- **Jaminan Kualitas Kode**:
  - Terverifikasi penuh oleh **141 kasus uji otomatis dan 3.171 assertion (100% PASS)**.

---

## 2. Administrator Sekolah: Wizard Setup Tahun Ajaran

Dapat diakses melalui tombol **Setup Tahun Ajaran** pada Dasbor Administrator Sekolah atau menu sidebar **Setup Akademik** (`/admin/setup`).

### Langkah-langkah Wizard:
1. **Tahun Ajaran & Semester**: Pilih tahun ajaran yang ada atau buat baru beserta rentang tanggalnya, lalu tentukan semester aktif (Ganjil/Genap).
2. **Jurusan, Tingkat Kelas, & Rombel**: Pilih jurusan sekolah, tentukan tingkat kelas (misalnya Kelas X, XI, XII), dan tambahkan kode rombel (misalnya X-1, X-2).
3. **Mata Pelajaran**: Pilih mata pelajaran yang tersedia atau tambahkan kode mapel, nama mata pelajaran, dan beban Jam Pelajaran (JP).
4. **Penugasan Guru**: Tentukan guru pengajar untuk setiap mata pelajaran pada masing-masing rombel.
5. **Penempatan Siswa**: Pilih siswa yang sudah terdaftar atau unggah pratinjau data siswa baru dari berkas CSV ke rombel tujuan.
6. **Tinjauan & Konfirmasi**: Periksa ringkasan struktur sekolah, jadwal, dan penempatan siswa, lalu konfirmasi pembuatan dalam satu transaksi basis data (`DB::transaction`).

Penempatan siswa ke sebuah rombel secara otomatis mendaftarkan (*auto-enroll*) siswa tersebut ke seluruh mata pelajaran pada rombelnya tanpa menghapus riwayat nilai semester terdahulu.

---

## 3. Impor Data Siswa via CSV (*Student CSV Import*)

Formulir impor siswa mendukung berkas templat standar:

```csv
Student ID,Name,Email,Block
2026-0001,Nama Siswa,siswa@sekolah.sch.id,X-1
```

- **Format Berkas**: CSV berkode UTF-8 (mendukung UTF-8 BOM), maksimal 1 MB atau 500 baris per pengunggahan dengan tampilan pratinjau berhalaman.
- **Validasi Ketat**: Memeriksa kelengkapan kolom, format email, duplikasi NIS/email, ketersediaan rombel tujuan, dan kesesuaian peran akun.
- **Keamanan Akun Baru**: Akun siswa baru dibuat dengan tautan undangan aktivasi terenkripsi (*signed URL*) bertenggat 60 menit agar siswa dapat menetapkan kata sandinya sendiri secara aman.

---

## 4. Salin Struktur Semester Sebelumnya (*Duplicate Previous Setup*)

Memudahkan pergantian semester dengan menyalin struktur dari periode sebelumnya:
- Administrator sekolah memilih tahun ajaran asal, semester, jurusan, dan tingkat kelas.
- Rombel serta kelas pelajaran baru dibuat dengan ID baru sementara data mata pelajaran, akun siswa, dan profil guru tetap dipakai ulang secara bersih tanpa menimpa arsip presensi maupun nilai lama.

---

## 5. Pengalaman Guru Pengajar & Wali Kelas

- **Dasbor Guru Terpadu**: Menampilkan ringkasan kelas yang diajar, jadwal mengajar hari ini, tugas yang menunggu dinilai, serta **Panel AI Deteksi Dini Siswa Berisiko**.
- **Penugasan & Buku Nilai**: Pembuatan tugas pelajaran dengan batas waktu dan lampiran berkas, penilaian massal (*bulk grading*) disertai catatan evaluasi perbaikan, serta rekap buku nilai kelas.
- **Presensi Harian Cepat**: Tombol aksi cepat **Tandai Semua Hadir**, ambang keterlambatan otomatis, tinjauan surat izin/sakit siswa, dan log audit presensi sekolah.

---

## 6. Pengalaman Siswa

- **Perlu Perhatian Segera**: Menampilkan daftar tugas pelajaran yang mendekati atau melewati tenggat waktu maupun yang perlu direvisi.
- **Progres Belajar & Evaluasi Mandiri AI**: Ringkasan persentase kehadiran, rata-rata nilai pelajaran, dan umpan balik konstruktif mandiri untuk menjaga kedisiplinan belajar.
- **Jadwal Pelajaran Mingguan**: Tampilan jadwal rapi per hari lengkap dengan jam pelajaran, mata pelajaran, guru pengajar, dan ruang kelas.
