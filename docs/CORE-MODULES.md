# Tiga Modul Inti Sekolah

Navigasi portal **AKSARA** dikelompokkan ke dalam tiga pilar utama kegiatan sekolah, ditambah halaman Beranda (*Dashboard*), AI Peringatan Dini, Pengumuman Sekolah, dan Profil Saya.

| Modul | Administrator Sekolah | Guru Pengajar | Siswa |
| --- | --- | --- | --- |
| **Manajemen Akademik** | Tahun ajaran, jurusan, tingkat kelas, rombel, mata pelajaran, data siswa, data guru, kelas/penugasan guru, akun pengguna | Kelas saya dan daftar siswa peserta kelas | Kelas saya, mata pelajaran, tingkat kelas, rombel, dan guru pengajar |
| **Materi & Pembelajaran** | Memantau aktivitas kelas dan pengumpulan tugas siswa | Pertemuan pelajaran, bahan ajar, tugas, pengumpulan, dan penilaian | Materi pelajaran, bahan ajar, tugas, dan pengumpulan |
| **Pemantauan & Evaluasi** | AI peringatan dini siswa, laporan presensi, dan laporan nilai | Pencatatan presensi harian, buku nilai kelas, AI peringatan dini, dan laporan | Presensi kehadiran, nilai, progres mandiri AI, dan jadwal pelajaran |

Pendaftaran kelas dan pengaturan jadwal pelajaran dapat diakses dari halaman admin **Kelas & Penugasan Guru** (`/admin/manage/teacher-assignments`), serta melalui `/admin/manage/enrollments` dan `/admin/manage/schedules` dengan validasi bentrok jadwal otomatis.

## Fitur Lanjutan yang Disembunyikan secara Bawaan

`config/lms.php` mengatur `show_advanced_features` ke `false` agar antarmuka sekolah tetap ringkas dan fokus. Dengan pengaturan bawaan ini, tampilan utama menyembunyikan:

- kuis pilihan ganda lanjutan,
- tautan laporan/ekspor tambahan,
- pusat notifikasi terpisah,
- pencarian global lanjut,
- formulir permohonan izin presensi mandiri,
- riwayat audit presensi rinci,
- riwayat versi pengumpulan tugas terdahulu,
- pengaturan ambang presensi global.

Ubah nilai tersebut menjadi `true` bila sekolah ingin menampilkan seluruh titik masuk fitur lanjutan. Pengaturan ini hanya memengaruhi tampilan antarmuka: seluruh rute, *controller*, model, tabel, dan riwayat akademik tetap terjaga utuh.

Rute `/classes` menerima parameter kueri `module=learning` atau `module=monitoring` yang mengarahkan guru maupun siswa ke bagian materi atau penilaian kelas yang sesuai dengan hak aksesnya.

## Penyempurnaan Antarmuka & Validasi

- Input bilangan bulat (misalnya nomor tingkat kelas dan urutan pertemuan pelajaran) memakai minimum 1 dan `step` 1; poin penilaian tetap mendukung nilai desimal.
- Label **Tingkat Kelas**, **Jurusan**, **Rombel**, **Mata Pelajaran**, **Data Siswa**, **Data Guru**, dan **Akun Pengguna** diseragamkan khusus untuk lingkungan sekolah.
- Teks materi/instruksi wajib serta unggahan berkas bahan ajar dan tugas divalidasi di peramban maupun di server.
- Formulir katalog tata usaha menampilkan pesan validasi per kolom dan menonaktifkan tombol simpan apabila relasi wajib belum tersedia.
- Halaman tugas menampilkan pengumpulan terbaru per siswa sambil tetap menyimpan seluruh riwayat revisi di basis data.
- Presensi di luar jendela edit tujuh hari guru ditampilkan sebagai kontrol baca-saja (*read-only*).
- Pengumuman guru tanpa kelas mengembalikan pesan validasi yang ramah tanpa halaman galat.

## Pengujian

Jalankan seluruh rangkaian uji dengan:

```sh
php artisan test
```

Hasil terverifikasi: **141 kasus uji (100% PASS)**. `CoreModulesTest` dan `EarlyWarningSystemTest` memverifikasi tautan navigasi setiap peran sekolah, pembatasan akses antar-kelas, serta pemantauan risiko akademik siswa.

## Catatan Hosting

Gunakan penyimpanan privat kompatibel S3 yang persisten untuk bahan ajar, berkas tugas siswa, dan foto profil apabila dideploy pada lingkungan *serverless* seperti Vercel. Penjelasan lengkap tersedia di [VERCEL.md](VERCEL.md).
