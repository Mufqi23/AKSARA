# Panduan Pengujian Otomatis

## Ringkasan

| Item | Keterangan |
| --- | --- |
| Perintah | `php artisan test` (alternatif: `composer test`) |
| Hasil terverifikasi saat ini | **146 kasus uji, 3.253 assertion**, semuanya lulus (`100% PASS`) |
| Basis data bawaan pengujian | SQLite `:memory:` (dikonfigurasi di `phpunit.xml`) |
| Runtime | PHP 8.2+ dengan ekstensi `pdo_sqlite` aktif |

## Menjalankan Pengujian

```sh
composer install
php artisan test
```

Pengujian berjalan secara terisolasi tanpa mengubah basis data aplikasi. Kelas dasar `tests/TestCase.php` secara ketat menolak eksekusi pengujian pada basis data apa pun selain `:memory:` atau basis data yang berakhiran `_test`.

### Menjalankan dengan MySQL Khusus Uji

1. Buat basis data **khusus pengujian**, misalnya `aksara_test`.
2. Salin `phpunit.xml` menjadi `phpunit.mysql.xml`, lalu sesuaikan variabel DB berikut:

   ```xml
   <env name="DB_CONNECTION" value="mysql"/>
   <env name="DB_DATABASE" value="aksara_test"/>
   <env name="DB_HOST" value="127.0.0.1"/>
   <env name="DB_PORT" value="3306"/>
   <env name="DB_USERNAME" value="your_test_database_user"/>
   <env name="DB_PASSWORD" value="your_test_database_password"/>
   ```

3. Jalankan:

   ```sh
   php vendor/phpunit/phpunit/phpunit -c phpunit.mysql.xml
   ```

Jangan pernah mengarahkan konfigurasi pengujian ke basis data sekolah sungguhan.

## Daftar Test Suites (13 Berkas Uji Fitur + Unit)

| Berkas Uji | Cakupan Utama |
| --- | --- |
| `ExampleTest` | Halaman depan (*landing page*) sekolah, indikator statistik, dan ketiadaan komponen lama |
| `SchoolDatasetHubTest` | Pusat Ekspor & Impor Dataset Sekolah: RBAC 403 Siswa, cakupan kelas Guru, Buku Kerja Terpadu 7-Sheet `.xlsx`, CSV `;`, dan proteksi bentrok/injeksi |
| `EarlyWarningSystemTest` | Mesin AI Deteksi Dini Siswa Berisiko, kalkulasi skor 4 faktor, otorisasi peran guru/siswa/admin, filter status, dan narasi pembimbingan |
| `LmsTest` | Alur inti sekolah: autentikasi, katalog admin, materi pelajaran, tugas, evaluasi kuis, presensi harian, laporan, dan pengumuman |
| `TwoFactorAuthTest` | Tantangan OTP email 2FA, masa berlaku 10 menit, dan pembatasan kirim ulang |
| `CoreModulesTest` | Navigasi modul sekolah per peran, fitur lanjutan tersembunyi, dan proteksi hak akses |
| `AcademicSetupServiceTest`, `AcademicSetupWizardTest` | Wizard penyiapan tahun ajaran, pratinjau, konfirmasi, dan *rollback* atomik |
| `MultiProgramSetupTest` | Penyiapan beberapa jurusan sekaligus dan penempatan siswa CSV bercakupan |
| `StudentCsvImportTest` | Validasi berkas CSV data siswa (NIS, nama, email, rombel) dan resolusi akun |
| `TeacherWorkflowTest` | Presensi harian massal oleh guru, penilaian tugas massal, status draf/terbit, dan riwayat siswa |
| `DashboardWorkflowTest` | Dasbor khusus Administrator Sekolah, Guru Pengajar, dan Siswa beserta cakupan datanya |
| `CurriculumImportTest` | Impor mata pelajaran, beban JP, prasyarat, idempotensi, dan integritas data |

## Cakupan Fungsional

- **Autentikasi & Keamanan Akun:** login/logout, pembatasan laju (*rate limit*), 2FA OTP email, akun ditangguhkan/dihapus, aktivasi *signed URL* sekali pakai, reset kata sandi, dan proteksi profil.
- **Tata Usaha / Admin Sekolah:** CRUD tahun ajaran, jurusan, tingkat kelas, rombel, mata pelajaran, siswa, guru, pendaftaran kelas otomatis, dan pencegahan bentrok jadwal mengajar guru maupun ruang kelas.
- **Kelas & Kegiatan Belajar:** pembatasan akses antar-kelas (*anti-IDOR*), pertemuan terbit/draf, unduhan berkas bahan ajar privat, pengumpulan tugas berversi, serta batas poin penilaian.
- **AI Peringatan Dini Siswa:** evaluasi kehadiran harian, ketepatan pengumpulan tugas, rata-rata nilai pelajaran, keaktifan evaluasi, serta isolasi visibilitas data antar-guru dan antar-siswa.
- **Presensi & Laporan Sekolah:** ambang keterlambatan yang dapat diatur, jendela edit 7 hari bagi guru, log audit koreksi admin, tinjauan izin/sakit, serta ekspor laporan PDF dan Excel (`.xlsx`).
