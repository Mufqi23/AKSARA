# Panduan Deployment Server Sekolah

## Kebutuhan Sistem

| Komponen | Kebutuhan |
| --- | --- |
| PHP | 8.2 atau lebih baru, kompatibel dengan `composer.lock` |
| Ekstensi PHP | ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, PDO, pdo_mysql, session, tokenizer, xml, xmlreader, xmlwriter, zip; aktifkan juga `pdo_sqlite` untuk menjalankan pengujian otomatis |
| Basis Data | MySQL 8.0+ dengan InnoDB dan `utf8mb4` |
| Dependensi | Composer 2. Tidak memerlukan server Node atau proses build frontend karena aset Bootstrap, CSS, dan JS sudah dibundel secara lokal |
| Server | Web server dengan HTTPS, PHP-FPM atau handler PHP setara, dan penjadwal (*scheduler*) |

## Deployment Produksi Baru

1. Ekstrak proyek di luar *document root* publik web. Arahkan virtual host **hanya** ke folder `aksara/public`.
2. Jalankan `composer install --no-dev --optimize-autoloader` di server untuk menyiapkan dependensi produksi.
3. Salin `.env.example` ke `.env`, lalu atur `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`, `APP_URL=https://portal.sekolah-anda.sch.id`, `SESSION_SECURE_COOKIE=true`, serta kredensial basis data dan SMTP sekolah.
4. Jalankan `php artisan key:generate` satu kali pada instalasi baru, dan pertahankan kunci ini saat pembaruan versi. `APP_TIMEZONE` bawaan adalah `Asia/Jakarta` dan `APP_LOCALE` bawaan `id`.
5. Buat basis data dan pengguna MySQL khusus dengan hak akses terbatas hanya ke basis data tersebut.
6. Jalankan `php artisan migrate --force`, lalu `php artisan lms:create-admin` untuk membuat akun administrator sekolah pertama secara interaktif dan terverifikasi. Jangan menjalankan seeder akun demo di lingkungan produksi.
7. Berikan izin tulis akun layanan PHP ke folder `storage/` dan `bootstrap/cache/`.
8. Jalankan `php artisan storage:link` untuk disk publik. Berkas bahan ajar pelajaran, pengumpulan tugas siswa, dan foto profil disimpan secara aman di `storage/app/private`.
9. Jalankan `php artisan optimize` setelah konfigurasi selesai. Setiap kali nilai `.env` diubah, jalankan `php artisan optimize:clear` lalu `php artisan optimize`.
10. Konfigurasikan penjadwal (*cron*), uji pengiriman email undangan akun, reset kata sandi, dan kode OTP 2FA, kemudian verifikasi login melalui HTTPS sebelum membagikan akun kepada guru dan siswa.

## Keamanan Sesi dan Autentikasi

Nilai bawaan pada `config/session.php` dan `.env.example`:

```dotenv
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Dengan `SESSION_SECURE_COOKIE=true`, cookie sesi hanya dikirim melalui sambungan HTTPS. Guru, siswa, dan staf tata usaha yang mengaktifkan 2FA akan menerima kode OTP 6 digit lewat email (berlaku 10 menit).

## Konfigurasi SMTP Sekolah

Untuk lingkungan pengembangan lokal, `MAIL_MAILER=log` mencatat email ke `storage/logs/laravel.log`. Untuk pengiriman email sekolah sungguhan:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS=noreply@sekolah-anda.sch.id
MAIL_FROM_NAME="AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)"
```

### Undangan Akun, Reset Kata Sandi, dan Antrean

- Undangan akun memverifikasi alamat email sekaligus menetapkan kata sandi awal melalui satu tautan aktivasi bertanda tangan (*signed URL*) sekali pakai yang berlaku 60 menit.
- Undangan massal siswa dari berkas CSV pada wizard tahun ajaran diproses melalui antrean basis data agar impor ratusan siswa tetap responsif. Jalankan *queue worker* terawasi (*supervised*):

  ```sh
  php artisan queue:work database --queue=default --tries=3 --timeout=30
  ```

## Penjadwal (*Scheduler*)

Di server Linux, jalankan setiap menit melalui cron:

```cron
* * * * * cd /var/www/aksara && php artisan schedule:run >> /dev/null 2>&1
```

Di Windows Server, gunakan Task Scheduler untuk menjalankan `php artisan schedule:run` setiap menit pada direktori proyek.

| Perintah | Jadwal | Fungsi |
| --- | --- | --- |
| `lms:deadlines` | Setiap jam | Mengirim pengingat in-app untuk tugas pelajaran yang belum dikumpulkan dalam 24 jam sebelum tenggat |
| `lms:expire-quizzes` | Setiap menit | Menyelesaikan dan menilai percobaan kuis yang melewati batas waktu |

## Batas Unggahan Berkas

- Atur `upload_max_filesize=20M` dan `post_max_size=24M` di konfigurasi PHP (`php.ini`), serta `client_max_body_size 24m;` pada Nginx.
- Aplikasi menerima berkas bahan ajar dan tugas hingga 20 MB serta foto profil hingga 2 MB pada lokasi privat teracak yang diperiksa izin kelasnya saat diunduh.

## Contoh Konfigurasi Nginx

```nginx
root /var/www/aksara/public;
index index.php;
client_max_body_size 24m;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location = /index.php {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
}

location ~ \.php$ { return 404; }
location ~ /\.(?!well-known).* { deny all; }
```

Untuk penerapan di Vercel, lihat [VERCEL.md](VERCEL.md).

## Operasional Sekolah

- Gunakan fitur penangguhan (*suspend*) akun untuk menonaktifkan akses pengguna tanpa menghapus riwayat nilai dan presensi siswa.
- Buat rombel baru untuk tahun ajaran atau semester baru melalui wizard **Setup Tahun Ajaran** agar arsip nilai semester terdahulu tetap rapi dan utuh.
- Jurusan dan tingkat kelas siswa mengacu langsung pada rombel aktifnya sehingga tidak terjadi ketidaksesuaian data penempatan siswa.
