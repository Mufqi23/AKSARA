# Penyiapan Multi-Jurusan dalam Satu Tahun Ajaran

Wizard **Setup Tahun Ajaran** (`/admin/setup`) dapat mengonfigurasi beberapa jurusan sekaligus dalam satu tahun ajaran dan semester. Setiap jurusan memilih tingkat kelas (misalnya Kelas X, XI, XII) dan rombongan belajar (*rombel*) masing-masing. Mata pelajaran serta guru pengajar ditugaskan secara eksplisit ke rombel yang dipilih, dan penempatan siswa diselesaikan ke rombel yang tepat.

## Alur Kerja

1. Pilih atau buat **Tahun Ajaran**, lalu pilih semester aktif (Ganjil/Genap).
2. Tambahkan **Jurusan** (pilih jurusan yang sudah terdaftar), lalu tambahkan tingkat kelasnya. Pilih tingkat kelas yang sudah ada atau masukkan nama baru beserta nomor tingkat (bilangan bulat). Tambahkan rombel di bawah setiap tingkat kelas. Ulangi untuk jurusan lainnya.
3. Pilih **Mata Pelajaran** yang sudah ada atau masukkan rincian mata pelajaran baru beserta beban JP-nya.
4. Pilih mata pelajaran dan **Guru Pengajar**, lalu centang rombel tujuan. Setiap pilihan menampilkan informasi Jurusan, Tingkat Kelas, dan Rombel secara jelas.
5. Pilih **Siswa** yang sudah terdaftar beserta rombel tujuannya, atau unggah pratinjau berkas CSV data siswa.
6. Tinjau struktur Jurusan → Tingkat Kelas → Rombel, mata pelajaran, guru pengajar, serta penempatan siswa, lalu konfirmasi pembuatan.

Langkah-langkah draf tidak membuat rekaman sebagian di basis data. Pembuatan akhir berjalan secara utuh dalam satu transaksi basis data (`DB::transaction`). Setiap galat validasi atau bentrok jadwal akan membatalkan (*rollback*) seluruh penyiapan secara otomatis.

## Relasi Basis Data

- Rombel (`blocks`) menyimpan Tahun Ajaran, Semester, Jurusan, dan Tingkat Kelas.
- Kelas (`teacher_assignments`) menyimpan Rombel, Mata Pelajaran, dan Guru Pengajar.
- Penempatan dan pendaftaran kelas siswa memakai ID rombel dan kelas tersebut.

Nama rombel yang sama didukung pada cakupan Jurusan/Tingkat Kelas yang berbeda (misalnya IPA → Kelas X → X-1 dan IPS → Kelas X → X-1 memiliki ID rombel berbeda). Nama rombel duplikat dalam jurusan, tingkat kelas, tahun ajaran, dan semester yang sama akan ditolak.

## Penempatan Siswa melalui CSV

Format empat kolom (`Student ID,Name,Email,Block`) didukung apabila nama rombel bersifat unik. Untuk penyiapan multi-jurusan yang memiliki kode rombel serupa, gunakan format enam kolom:

```csv
Student ID,Name,Email,Program,Year Level,Block
2026-0001,Siswa Contoh IPA,siswa.ipa@sekolah.sch.id,IPA,10,X-1
2026-0002,Siswa Contoh IPS,siswa.ips@sekolah.sch.id,IPS,10,X-1
```

- `Student ID` berisi Nomor Induk Siswa (NIS).
- `Program` menerima kode atau nama jurusan dari katalog sekolah.
- `Year Level` menerima nama tingkat kelas atau nomor tingkat (bilangan bulat).
- `Block` menerima kode/nama rombel tujuan.

Saat siswa dipindahkan ke rombel tahun ajaran berjalan, riwayat nilai, presensi harian, dan pengumpulan tugas pada semester sebelumnya tetap dipertahankan secara aman. Akun siswa baru dari CSV menerima tautan aktivasi terenkripsi (*signed URL*) melalui antrean basis data (lihat [DEPLOYMENT.md](DEPLOYMENT.md) dan [VERCEL.md](VERCEL.md)).

## Batasan per Penyiapan

| Batasan per penyiapan | Nilai maksimum |
| --- | --- |
| Kelompok jurusan | 20 |
| Tingkat kelas per jurusan | 12 |
| Total rombel | 30 |
| Mata pelajaran | 50 |
| Kelas pelajaran | 300 |
| Siswa | 500 |
| Relasi pendaftaran kelas | 10.000 |

## Pengujian

`MultiProgramSetupTest` menguji penyiapan multi-jurusan dengan rombel berbeda, penugasan mata pelajaran dan guru pengajar, impor CSV siswa bercakupan, pemeliharaan arsip riwayat kelas lama, serta *rollback* transaksi penuh bila terjadi bentrok.

Jalankan `php artisan test`; hasil rangkaian pengujian terverifikasi adalah **141 kasus uji, 3.171 assertion (100% PASS)**.
