# Panduan Penerapan (Deployment) AKSARA di Vercel

Dokumen ini menjelaskan konfigurasi penerapan **AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)** pada platform Vercel menggunakan *runtime* komunitas `vercel-php` berbasis PHP 8.3. Fungsi serverless pada `api/index.php` memuat `public/index.php`, sementara `vercel.json` mendistribusikan aset statis publik secara langsung dan mengalirkan rute lainnya ke Laravel.

Impor repositori GitHub ke Vercel dengan direktori utama (*root*) sebagai project root dan pilih preset framework **Other**. Jangan menetapkan Output Directory khusus atau mengubah fungsi serverless menjadi build statis.

## Variabel Lingkungan Produksi (*Environment Variables*)

Tambahkan variabel berikut di **Vercel Project Settings → Environment Variables**. Buat kunci aplikasi baru dengan `php artisan key:generate --show` di komputer lokal terpercaya dan jangan pernah mengunggah `APP_KEY` ke repositori publik.

```dotenv
APP_NAME="AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)"
APP_ENV=production
APP_KEY=base64:<kunci-aplikasi-terenkripsi>
APP_DEBUG=false
APP_URL=https://<domain-portal-sekolah-anda>
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=<host-mysql-eksternal>
DB_PORT=3306
DB_DATABASE=<nama-database>
DB_USERNAME=<user-database>
DB_PASSWORD=<password-database>

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=local
LMS_PRIVATE_FILESYSTEM=s3
AWS_ACCESS_KEY_ID=<access-key-objek-storage>
AWS_SECRET_ACCESS_KEY=<secret-key-objek-storage>
AWS_DEFAULT_REGION=<wilayah-storage>
AWS_BUCKET=<nama-bucket-privat>
AWS_ENDPOINT=<endpoint-s3-jika-kompatibel>
AWS_USE_PATH_STYLE_ENDPOINT=false

MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=<alamat-email-smtp>
MAIL_PASSWORD=<password-aplikasi-email>
MAIL_FROM_ADDRESS=<alamat-pengirim-terverifikasi>
MAIL_FROM_NAME="AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)"
```

Biarkan `AWS_ENDPOINT` kosong jika menggunakan AWS S3 resmi, atau isi dengan endpoint penyedia kompatibel S3 (seperti Cloudflare R2 atau MinIO). Pastikan *bucket* penyimpanan berstatus privat. Konfigurasi `LMS_PRIVATE_FILESYSTEM=s3` mengarahkan penyimpanan bahan ajar pelajaran, berkas tugas siswa, dan foto profil ke *bucket* privat secara persisten karena direktori `/tmp` pada Vercel bersifat sementara.

## Basis Data dan Penjadwal (*Scheduler*)

Siapkan basis data MySQL eksternal dan jalankan migrasi sebelum membuka akses portal sekolah:

```bash
php artisan migrate --force
```

Untuk membuat akun administrator sekolah pertama, jalankan perintah interaktif:

```bash
php artisan lms:create-admin
```

Untuk tugas terjadwal (`lms:deadlines` pengingat tenggat tugas siswa setiap jam dan `lms:expire-quizzes` penyelesaian kuis setiap menit), sediakan worker atau *cron host* eksternal yang mengeksekusi:

```bash
php artisan schedule:run
```

## Pertimbangan Teknis Platform

1. **Antrean Undangan Akun Siswa**: Pengiriman massal email aktivasi siswa dari wizard Tahun Ajaran disimpan ke tabel `jobs`. Jalankan worker eksternal (`php artisan queue:work database --queue=default --tries=3 --timeout=30`) untuk memproses pengiriman email secara asinkron.
2. **Batas Ukuran Payload**: Fungsi serverless Vercel memiliki batas muatan *request/response* sebesar 4,5 MB. Untuk unggahan bahan ajar atau berkas tugas berukuran hingga 20 MB secara langsung melalui server aplikasi, disarankan menggunakan VPS/hosting PHP standar.
3. **Konfigurasi Variabel Input**: Untuk formulir presensi harian atau penilaian massal pada rombel berukuran besar, pastikan `max_input_vars` bernilai minimal 3500.

Setelah seluruh variabel terhubung, uji login HTTPS untuk peran Administrator Sekolah, Guru Pengajar, dan Siswa, unduhan bahan ajar privat, modul AI Peringatan Dini, pengiriman OTP 2FA, serta ekspor laporan.
