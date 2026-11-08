<#
.SYNOPSIS
    Menyiapkan paket AKSARA siap unggah untuk hosting tanpa terminal (contoh: InfinityFree).

.DESCRIPTION
    Hasil ada di folder build\infinityfree\htdocs. Isi folder htdocs diunggah apa adanya
    ke folder htdocs di hosting lewat FTP. Kode aplikasi disimpan di htdocs\aksara-core
    dan dikunci agar tidak bisa dibuka dari browser. Video latar ikut dikemas, kecuali
    skrip dijalankan dengan -TanpaVideo.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File scripts\build-infinityfree.ps1
#>
param([switch]$TanpaVideo)

$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..')
$out = Join-Path $root 'build\infinityfree'
$htdocs = Join-Path $out 'htdocs'
$core = Join-Path $htdocs 'aksara-core'

if (Test-Path $out) { Remove-Item $out -Recurse -Force }
New-Item -ItemType Directory -Path $core -Force | Out-Null

Write-Host '1/5 Menyalin kode aplikasi...'
$skipDirs = @('.git', '.vscode', '.idea', 'node_modules', 'vendor', 'build', 'tests', 'public', 'docker', '.vercel')
Get-ChildItem -Path $root -Force | Where-Object { $skipDirs -notcontains $_.Name } | ForEach-Object {
    if ($_.Name -in @('.env', 'aksara-submission.zip', 'Dockerfile', '.dockerignore', 'vercel.json', '.vercelignore', '.phpunit.result.cache')) { return }
    Copy-Item $_.FullName -Destination $core -Recurse -Force
}

# Bersihkan data lokal yang tidak boleh ikut terunggah
Get-ChildItem (Join-Path $core 'storage\logs') -File -ErrorAction SilentlyContinue | Where-Object Name -ne '.gitignore' | Remove-Item -Force
foreach ($dir in 'storage\framework\views', 'storage\framework\cache\data', 'storage\framework\sessions', 'bootstrap\cache') {
    Get-ChildItem (Join-Path $core $dir) -File -ErrorAction SilentlyContinue | Where-Object Name -ne '.gitignore' | Remove-Item -Force
}
Get-ChildItem (Join-Path $core 'database') -Filter '*.sqlite*' -ErrorAction SilentlyContinue | Remove-Item -Force
foreach ($dir in 'storage\framework\testing', 'storage\app\private\demo') {
    Remove-Item (Join-Path $core $dir) -Recurse -Force -ErrorAction SilentlyContinue
}

Write-Host '2/5 Menyalin vendor lalu membuang paket pengembangan...'
Copy-Item (Join-Path $root 'vendor') -Destination $core -Recurse -Force
Push-Location $core
try {
    composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
} finally {
    Pop-Location
}

Write-Host '3/5 Menyiapkan folder publik...'
$publicSkip = @('storage', 'hot')
Get-ChildItem -Path (Join-Path $root 'public') -Force | ForEach-Object {
    if ($publicSkip -contains $_.Name) { return }
    Copy-Item $_.FullName -Destination $htdocs -Recurse -Force
}
if ($TanpaVideo) {
    Get-ChildItem (Join-Path $htdocs 'images') -Filter '*.mp4' -ErrorAction SilentlyContinue | Remove-Item -Force
}

@'
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$core = __DIR__.'/aksara-core';

require $core.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $core.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
'@ | Set-Content -Path (Join-Path $htdocs 'index.php') -Encoding UTF8

@'
# Kode aplikasi tidak boleh dibuka dari browser.
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
</IfModule>
'@ | Set-Content -Path (Join-Path $core '.htaccess') -Encoding ASCII

Write-Host '4/5 Membuat file .env produksi...'
$appKey = 'base64:' + [Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Maximum 256 }) -as [byte[]])
$setupToken = -join ((48..57) + (97..122) | Get-Random -Count 40 | ForEach-Object { [char]$_ })

@"
APP_NAME="AKSARA"
APP_ENV=production
APP_KEY=$appKey
APP_DEBUG=false
APP_URL=https://GANTI-DENGAN-ALAMAT-WEB-ANDA
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=GANTI-HOST-MYSQL
DB_PORT=3306
DB_DATABASE=GANTI-NAMA-DATABASE
DB_USERNAME=GANTI-USER-DATABASE
DB_PASSWORD=GANTI-PASSWORD-DATABASE

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
LMS_PRIVATE_FILESYSTEM=local

MAIL_MAILER=log

# Pemasangan awal. Hapus tiga baris di bawah setelah halaman setup selesai dijalankan.
SETUP_TOKEN=$setupToken
SETUP_ADMIN_NAME="Administrator Sekolah"
SETUP_ADMIN_EMAIL=GANTI-EMAIL-ADMIN
SETUP_ADMIN_PASSWORD=GANTI-PASSWORD-ADMIN-MINIMAL-12-HURUF-ADA-BESAR-KECIL-DAN-ANGKA
"@ | Set-Content -Path (Join-Path $core '.env') -Encoding ASCII

Write-Host '5/5 Menghitung ukuran paket...'
$files = Get-ChildItem $htdocs -Recurse -File
$sizeMb = [math]::Round(($files | Measure-Object Length -Sum).Sum / 1MB, 1)
Write-Host ''
Write-Host "Selesai. Jumlah file: $($files.Count), ukuran: $sizeMb MB"
Write-Host "Folder yang diunggah : $htdocs"
Write-Host "Alamat setup nanti   : https://ALAMAT-WEB-ANDA/setup/$setupToken"
Write-Host 'Edit dulu file aksara-core\.env (semua nilai GANTI-...) sebelum diunggah.'

