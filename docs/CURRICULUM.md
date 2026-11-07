# Sistem Impor Kurikulum Sekolah

Berkas `database/data/demo_courses_import.csv` adalah berkas demo administratif untuk pengujian dan inisialisasi struktur mata pelajaran massal. SHA-256: `99e91ebed9125676ef21ead49c15c7cd9bf25055cef7f001401690f9a30de4cb`.

Berkas sampel tersebut memuat **221 mata pelajaran**, 10 kode jurusan/program di katalog uji, tingkat kelas numerik, dua semester, serta 133 relasi prasyarat mata pelajaran.

## Format CSV

Berkas sumber wajib memiliki tepat sembilan header berikut secara berurutan:

```csv
program_code,year_level,semester,course_code,course_title,units,lecture_units,laboratory_units,prerequisite
```

## Struktur Data yang Dipertahankan

- Mata pelajaran disimpan di tabel `subjects`, dan seluruh *foreign key* kelas mempertahankan ID-nya.
- Dua migrasi aditif menambahkan penempatan opsional jurusan/tingkat kelas/semester, beban JP total/teori/praktik dalam desimal, serta tabel `subject_prerequisites`.
- Mata pelajaran umum/bersama (*shared*) mempertahankan kode unik global melalui kolom tergenerasi `shared_code`. Mata pelajaran kurikulum unik berdasarkan kombinasi jurusan, tingkat kelas, semester, dan kode mapel.
- Tingkat kelas tetap memakai katalog numerik yang unik secara global.
- Nilai semester `1st` dan `2nd` dipetakan ke semester 1 (Ganjil) dan 2 (Genap).
- Struktur kurikulum dapat dipakai ulang lintas tahun ajaran; berkas CSV tidak memuat tahun ajaran dan importer tidak mengubah data tahun ajaran aktif.

Importer **tidak** membuat akun pengguna, rombel, kelas, jadwal pelajaran, pendaftaran siswa, maupun data kegiatan belajar mengajar.

## Perintah Konsol

```sh
php artisan lms:import-curriculum --validate
php artisan migrate --path=database/migrations/2026_10_04_000000_add_curriculum_to_subjects.php --force
php artisan migrate --path=database/migrations/2026_10_04_000001_preserve_shared_subject_code_uniqueness.php --force
php artisan lms:import-curriculum --dry-run
php artisan lms:import-curriculum --force
```

| Opsi | Perilaku |
| --- | --- |
| argumen `file` | Opsional; bawaannya memakai berkas CSV contoh yang disertakan |
| `--validate` | Hanya memvalidasi format berkas sumber tanpa akses basis data |
| `--dry-run` | Memeriksa sumber dan rekaman basis data yang ada tanpa menyimpan perubahan |
| `--force` | Wajib disertakan saat menulis data di lingkungan produksi |

## Validasi dan Keamanan Pengulangan (*Idempotency*)

- Berkas sumber harus memiliki sembilan header yang tepat, pengodean UTF-8 valid, kode jurusan/tingkat kelas/semester yang sah, dan nilai beban JP desimal yang konsisten.
- Beban JP total harus sama dengan JP teori ditambah JP praktik.
- Baris duplikat dalam cakupan yang sama, prasyarat yang tidak ditemukan/ambigu, prasyarat ke diri sendiri, maupun siklus prasyarat akan dibatalkan sebelum ada penulisan basis data.
- Mata pelajaran disimpan terlebih dahulu, lalu relasi ID prasyarat diselesaikan dalam satu transaksi basis data yang sama.
- Menjalankan ulang impor yang sama tidak akan menggandakan mata pelajaran ataupun relasi prasyarat.

## Alur Kerja Administrator Sekolah

- **Jurusan** (`/admin/manage/programs`) mengelola daftar jurusan sekolah.
- **Mata Pelajaran** (`/admin/manage/subjects`) menyediakan filter jurusan, tingkat kelas, dan semester, rincian JP teori/praktik, serta tampilan prasyarat mata pelajaran.
- **Wizard Setup Tahun Ajaran** menampilkan mata pelajaran yang sesuai dengan cakupan jurusan dan tingkat kelas rombel yang dipilih ditambah mata pelajaran umum bersama.
- Baik wizard maupun formulir penugasan guru menolak penugasan mata pelajaran lintas jurusan/tingkat kelas/semester yang tidak sesuai cakupan kurikulumnya.

## Pengujian

`CurriculumImportTest` memverifikasi ketepatan judul/kode/JP dan prasyarat mata pelajaran, sifat idempoten, keamanan `--dry-run`, keunikan basis data, *rollback* transaksi, serta pembuatan kelas lewat wizard tahun ajaran.

Sebelum rilis, jalankan:

```sh
php artisan test
php artisan view:cache
```

Hasil rangkaian pengujian saat ini: **141 kasus uji (100% PASS)**.
