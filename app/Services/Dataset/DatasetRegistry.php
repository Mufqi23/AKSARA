<?php

namespace App\Services\Dataset;

use App\Models\User;

class DatasetRegistry
{
    public static function canOperate(User $user): bool
    {
        return in_array($user->role, ['admin', 'teacher'], true);
    }

    public static function canAccessModule(User $user, string $module, string $operation = 'export'): bool
    {
        if (! self::canOperate($user)) {
            return false;
        }

        if ($module === 'master_bundle') {
            return $user->role === 'admin';
        }

        $meta = self::modules()[$module] ?? null;
        if (! $meta) {
            return false;
        }

        if ($operation === 'import' && ! ($meta['importable'] ?? false)) {
            return false;
        }

        if ($operation === 'export' && ! ($meta['exportable'] ?? false)) {
            return false;
        }

        return in_array($user->role, $meta['roles'], true);
    }

    public static function visibleModules(User $user): array
    {
        if (! self::canOperate($user)) {
            return [];
        }

        return array_filter(
            self::modules(),
            fn (array $meta) => in_array($user->role, $meta['roles'], true)
        );
    }

    public static function masterBundleSheets(): array
    {
        return [
            '01_Tahun_Ajaran' => [
                'module' => 'academic_years',
                'headers' => ['nama_tahun_ajaran', 'tanggal_mulai', 'tanggal_selesai'],
                'sample' => [['2026/2027', '2026-07-13', '2027-06-19']],
            ],
            '02_Jurusan_Tingkat' => [
                'module' => 'programs_levels',
                'headers' => ['kode_jurusan', 'nama_jurusan', 'nomor_tingkat', 'nama_tingkat'],
                'sample' => [
                    ['MIPA', 'Matematika dan Ilmu Pengetahuan Alam', '10', 'Kelas X'],
                    ['IPS', 'Ilmu Pengetahuan Sosial', '11', 'Kelas XI'],
                    ['', '', '12', 'Kelas XII'],
                ],
            ],
            '03_Rombel' => [
                'module' => 'blocks',
                'headers' => ['tahun_ajaran', 'semester', 'kode_jurusan', 'nomor_tingkat', 'nama_rombel'],
                'sample' => [
                    ['2026/2027', '1', 'MIPA', '10', 'X-MIPA-1'],
                    ['2026/2027', '1', 'IPS', '10', 'X-IPS-1'],
                ],
            ],
            '04_Data_Guru' => [
                'module' => 'teachers',
                'headers' => ['nip', 'nama_lengkap', 'email', 'status_akun'],
                'sample' => [
                    ['198504122010011001', 'Budi Santoso, S.Pd.', 'budi.santoso@sekolah.sch.id', 'active'],
                    ['198908202014022003', 'Siti Rahmawati, M.Pd.', 'siti.rahma@sekolah.sch.id', 'active'],
                ],
            ],
            '05_Mata_Pelajaran' => [
                'module' => 'subjects',
                'headers' => ['kode_mapel', 'nama_mapel', 'jp_total', 'jp_teori', 'jp_praktik', 'kode_jurusan', 'nomor_tingkat', 'semester', 'prasyarat', 'deskripsi', 'status'],
                'sample' => [
                    ['MAT-10', 'Matematika Wajib X', '4', '4', '0', '', '', '', '', 'Penguasaan konsep aljabar dan fungsi dasar.', 'active'],
                    ['FIS-10', 'Fisika Dasar X', '3', '2', '1', 'MIPA', '10', '1', '', 'Besaran, gerak lurus, dan hukum Newton.', 'active'],
                ],
            ],
            '06_Kelas_dan_Jadwal' => [
                'module' => 'bundle_classes',
                'headers' => ['nip_guru', 'kode_mapel', 'nama_rombel', 'tahun_ajaran', 'semester', 'hari', 'jam_mulai', 'jam_selesai', 'ruang_kelas'],
                'sample' => [
                    ['198504122010011001', 'MAT-10', 'X-MIPA-1', '2026/2027', '1', 'Senin', '07:30', '09:00', 'Ruang X-1'],
                    ['198908202014022003', 'FIS-10', 'X-MIPA-1', '2026/2027', '1', 'Selasa', '09:15', '10:45', 'Lab Fisika'],
                ],
            ],
            '07_Data_Siswa' => [
                'module' => 'students',
                'headers' => ['nis', 'nama_lengkap', 'email', 'tahun_ajaran', 'semester', 'kode_jurusan', 'nomor_tingkat', 'nama_rombel', 'status_akun'],
                'sample' => [
                    ['202610001', 'Andi Pratama', 'andi.pratama@siswa.sch.id', '2026/2027', '1', 'MIPA', '10', 'X-MIPA-1', 'active'],
                    ['202610002', 'Nabila Putri', 'nabila.putri@siswa.sch.id', '2026/2027', '1', 'MIPA', '10', 'X-MIPA-1', 'active'],
                ],
            ],
        ];
    }

    public static function modules(): array
    {
        return [
            'academic_years' => [
                'category' => 'master',
                'title' => 'Tahun Ajaran & Kalender',
                'badge' => 'Data Induk',
                'description' => 'Kelola daftar tahun ajaran sekolah beserta rentang tanggal mulai dan berakhir.',
                'roles' => ['admin'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => false,
                'required_headers' => ['nama_tahun_ajaran', 'tanggal_mulai', 'tanggal_selesai'],
                'headers' => ['nama_tahun_ajaran', 'tanggal_mulai', 'tanggal_selesai'],
                'sample' => [['2026/2027', '2026-07-13', '2027-06-19']],
            ],
            'programs_levels' => [
                'category' => 'master',
                'title' => 'Jurusan & Tingkat Kelas',
                'badge' => 'Data Induk',
                'description' => 'Impor dan ekspor daftar jurusan/peminatan (MIPA, IPS, RPL, TKJ) serta jenjang tingkat kelas (X–XII).',
                'roles' => ['admin'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => false,
                'required_headers' => ['kode_jurusan', 'nama_jurusan', 'nomor_tingkat', 'nama_tingkat'],
                'headers' => ['kode_jurusan', 'nama_jurusan', 'nomor_tingkat', 'nama_tingkat'],
                'sample' => [['MIPA', 'Matematika dan Ilmu Pengetahuan Alam', '10', 'Kelas X']],
            ],
            'blocks' => [
                'category' => 'master',
                'title' => 'Rombongan Belajar (Rombel)',
                'badge' => 'Data Induk',
                'description' => 'Susun daftar rombel kelas per tahun ajaran, semester, jurusan, dan tingkat kelas secara massal.',
                'roles' => ['admin'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => false,
                'required_headers' => ['tahun_ajaran', 'semester', 'kode_jurusan', 'nomor_tingkat', 'nama_rombel'],
                'headers' => ['tahun_ajaran', 'semester', 'kode_jurusan', 'nomor_tingkat', 'nama_rombel'],
                'sample' => [['2026–2027', '1', 'BSIT', '3', '3L']],
            ],
            'teachers' => [
                'category' => 'master',
                'title' => 'Data Guru & Pendidik',
                'badge' => 'Data Induk',
                'description' => 'Daftarkan akun dan NIP seluruh guru pengajar sekaligus secara praktis.',
                'roles' => ['admin'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => false,
                'required_headers' => ['nip', 'nama_lengkap', 'email'],
                'headers' => ['nip', 'nama_lengkap', 'email', 'status_akun'],
                'sample' => [['198705112012011002', 'Hendra Wijaya, S.Pd.', 'hendra.wijaya@sekolah.sch.id', 'active']],
            ],
            'students' => [
                'category' => 'master',
                'title' => 'Data Siswa & Penempatan Rombel',
                'badge' => 'Data Induk',
                'description' => 'Masukkan daftar siswa (NIS, nama, email, rombel) dan daftarkan otomatis ke seluruh mata pelajaran rombelnya.',
                'roles' => ['admin'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => false,
                'required_headers' => ['nis', 'nama_lengkap', 'email', 'nama_rombel'],
                'headers' => ['nis', 'nama_lengkap', 'email', 'tahun_ajaran', 'semester', 'kode_jurusan', 'nomor_tingkat', 'nama_rombel', 'status_akun'],
                'sample' => [['202610099', 'Rizky Ramadhan', 'rizky.ramadhan@siswa.sch.id', '2026–2027', '1', 'BSIT', '3', '3J', 'active']],
            ],
            'subjects' => [
                'category' => 'master',
                'title' => 'Katalog Mata Pelajaran & JP',
                'badge' => 'Kurikulum',
                'description' => 'Kelola mata pelajaran sekolah, beban Jam Pelajaran (JP) teori/praktik, serta relasi prasyarat mapel.',
                'roles' => ['admin'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => false,
                'required_headers' => ['kode_mapel', 'nama_mapel', 'jp_total'],
                'headers' => ['kode_mapel', 'nama_mapel', 'jp_total', 'jp_teori', 'jp_praktik', 'kode_jurusan', 'nomor_tingkat', 'semester', 'prasyarat', 'deskripsi', 'status'],
                'sample' => [['BIN-10', 'Bahasa Indonesia X', '4', '4', '0', '', '', '', '', 'Teks laporan hasil observasi dan eksposisi.', 'active']],
            ],
            'teacher_assignments' => [
                'category' => 'master',
                'title' => 'SK Pembagian Tugas Mengajar',
                'badge' => 'Akademik',
                'description' => 'Hubungkan guru pengajar, mata pelajaran, dan rombel sekaligus menyinkronkan peserta kelas secara otomatis.',
                'roles' => ['admin'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => false,
                'required_headers' => ['nip_guru', 'kode_mapel', 'nama_rombel'],
                'headers' => ['nip_guru', 'kode_mapel', 'nama_rombel', 'tahun_ajaran', 'semester', 'kode_jurusan', 'nomor_tingkat'],
                'sample' => [['DEMO-T001', 'PF102', '3J', '2026–2027', '1', 'BSIT', '3']],
            ],
            'schedules' => [
                'category' => 'master',
                'title' => 'Jadwal Pelajaran & Ruang/Lab',
                'badge' => 'Akademik',
                'description' => 'Impor dan ekspor jadwal mingguan kelas lengkap dengan validasi otomatis bentrok jam guru maupun ruangan.',
                'roles' => ['admin'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => false,
                'required_headers' => ['nama_rombel', 'kode_mapel', 'hari', 'jam_mulai', 'jam_selesai', 'ruang_kelas'],
                'headers' => ['nama_rombel', 'kode_mapel', 'hari', 'jam_mulai', 'jam_selesai', 'ruang_kelas', 'tahun_ajaran', 'semester'],
                'sample' => [['3J', 'PF102', 'Kamis', '10:00', '11:30', 'Ruang Lab 3', '2026–2027', '1']],
            ],
            'announcements' => [
                'category' => 'master',
                'title' => 'Pengumuman Resmi Sekolah',
                'badge' => 'Administrasi',
                'description' => 'Publikasikan agenda akademik, jadwal evaluasi semester, atau informasi resmi sekolah secara terstruktur.',
                'roles' => ['admin'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => false,
                'required_headers' => ['judul', 'isi_pengumuman'],
                'headers' => ['judul', 'isi_pengumuman', 'kode_jurusan_opsional', 'nama_rombel_opsional'],
                'sample' => [['Jadwal Penilaian Tengah Semester', 'Pelaksanaan PTS dimulai Senin depan sesuai jadwal masing-masing rombel.', '', '']],
            ],
            'lessons' => [
                'category' => 'classroom',
                'title' => 'Silabus / Daftar Pertemuan Kelas',
                'badge' => 'KBM Kelas',
                'description' => 'Unggah rencana topik pertemuan satu semester sekaligus atau ekspor struktur materi kelas.',
                'roles' => ['admin', 'teacher'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => true,
                'required_headers' => ['nama_rombel', 'kode_mapel', 'urutan_pertemuan', 'judul_topik', 'isi_materi'],
                'headers' => ['nama_rombel', 'kode_mapel', 'urutan_pertemuan', 'judul_topik', 'ringkasan', 'isi_materi', 'status'],
                'sample' => [['3J', 'PF102', '2', 'Struktur Percabangan dan Perulangan', 'Memahami logika kendali alur program.', 'Pelajari contoh studi kasus pada modul dan kerjakan latihan mandiri.', 'published']],
            ],
            'assignments' => [
                'category' => 'classroom',
                'title' => 'Daftar Tugas Kelas',
                'badge' => 'KBM Kelas',
                'description' => 'Kelola daftar penugasan kelas lengkap dengan instruksi pengerjaan, batas waktu, dan bobot poin maksimal.',
                'roles' => ['admin', 'teacher'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => true,
                'required_headers' => ['nama_rombel', 'kode_mapel', 'judul_tugas', 'instruksi', 'tenggat_waktu', 'poin_maksimal'],
                'headers' => ['nama_rombel', 'kode_mapel', 'judul_tugas', 'deskripsi', 'instruksi', 'tenggat_waktu', 'poin_maksimal', 'status'],
                'sample' => [['3J', 'PF102', 'Tugas 2: Studi Kasus Algoritma', 'Latihan analisis masalah.', 'Tuliskan langkah penyelesaian masalah secara berurutan minimal 250 kata.', now()->addDays(7)->format('Y-m-d').' 23:59', '100', 'published']],
            ],
            'grades' => [
                'category' => 'classroom',
                'title' => 'Rekap Buku Nilai Siswa',
                'badge' => 'Evaluasi KBM',
                'description' => 'Unduh daftar siswa kelas, isi skor nilai dan catatan umpan balik di Excel, lalu unggah kembali ke buku nilai.',
                'roles' => ['admin', 'teacher'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => true,
                'required_headers' => ['nama_rombel', 'kode_mapel', 'judul_tugas', 'nis', 'skor'],
                'headers' => ['nama_rombel', 'kode_mapel', 'judul_tugas', 'nis', 'nama_siswa', 'skor', 'umpan_balik_guru', 'status_penilaian'],
                'sample' => [['3J', 'PF102', 'Activity 1: Apply the fundamentals', 'DEMO-2026-0001', 'Demo Jamie Flores', '95', 'Analisis sangat baik dan runtut.', 'graded']],
            ],
            'attendance' => [
                'category' => 'classroom',
                'title' => 'Presensi / Absensi Harian Kelas',
                'badge' => 'Evaluasi KBM',
                'description' => 'Impor dan ekspor data kehadiran harian siswa (Hadir, Terlambat, Izin/Sakit, Alpa) lengkap dengan riwayat pencatatan.',
                'roles' => ['admin', 'teacher'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => true,
                'required_headers' => ['nama_rombel', 'kode_mapel', 'tanggal', 'jam_mulai', 'jam_selesai', 'nis', 'status_kehadiran'],
                'headers' => ['nama_rombel', 'kode_mapel', 'tanggal', 'jam_mulai', 'jam_selesai', 'nis', 'nama_siswa', 'status_kehadiran', 'menit_terlambat', 'keterangan'],
                'sample' => [['3J', 'PF102', today()->format('Y-m-d'), '08:00', '09:30', 'DEMO-2026-0001', 'Demo Jamie Flores', 'Hadir', '0', 'Hadir tepat waktu']],
            ],
            'quiz_questions' => [
                'category' => 'classroom',
                'title' => 'Bank Soal Kuis Pilihan Ganda',
                'badge' => 'KBM Kelas',
                'description' => 'Impor puluhan butir soal pilihan ganda (Opsi A–D, kunci jawaban, bobot poin) sekaligus ke dalam kelas.',
                'roles' => ['admin', 'teacher'],
                'importable' => true,
                'exportable' => true,
                'class_scoped' => true,
                'required_headers' => ['nama_rombel', 'kode_mapel', 'judul_kuis', 'pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'kunci_jawaban', 'poin'],
                'headers' => ['nama_rombel', 'kode_mapel', 'judul_kuis', 'durasi_menit', 'pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'kunci_jawaban', 'poin', 'status_kuis'],
                'sample' => [['3J', 'PF102', 'Latihan Evaluasi Bab 2', '20', 'Struktur kontrol untuk mengulang blok perintah disebut?', 'Variabel', 'Perulangan (Loop)', 'Konstanta', 'Komentar', 'B', '10', 'draft']],
            ],
            'early_warning' => [
                'category' => 'classroom',
                'title' => 'Laporan AI Deteksi Dini Siswa Berisiko',
                'badge' => 'Analitik AI (Ekspor)',
                'description' => 'Ekspor hasil pemantauan risiko siswa beserta diagnosis dan rekomendasi pembimbingan AI untuk rapat Wali Kelas & BK.',
                'roles' => ['admin', 'teacher'],
                'importable' => false,
                'exportable' => true,
                'class_scoped' => true,
                'required_headers' => [],
                'headers' => ['nis', 'nama_siswa', 'jurusan', 'rombel', 'kehadiran_persen', 'jumlah_alpa', 'tugas_terlewat', 'rata_nilai', 'skor_risiko', 'status_risiko', 'rekomendasi_ai'],
                'sample' => [],
            ],
        ];
    }
}

