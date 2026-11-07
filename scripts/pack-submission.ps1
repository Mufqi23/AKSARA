# Script Pemaket Bersih untuk Pengumpulan Proyek AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)
# Mengecualikan: vendor/, node_modules/, .env, .git/, cache, dan file sensitif/besar

$global:ProgressPreference = 'SilentlyContinue'
Add-Type -AssemblyName System.IO.Compression.FileSystem
$rootPath = Split-Path -Parent $PSScriptRoot
$zipName = "aksara-submission.zip"
$outputPath = Join-Path $rootPath $zipName

Write-Host "===> Menyiapkan paket pengumpulan bersih..." -ForegroundColor Cyan

if (Test-Path $outputPath) {
    Remove-Item -Force $outputPath
    Write-Host "Berkas ZIP lama dihapus." -ForegroundColor Yellow
}

$tempFolder = Join-Path $env:TEMP ("aksara_pack_" + [System.Guid]::NewGuid().ToString())
New-Item -ItemType Directory -Path $tempFolder | Out-Null

$excludeItems = @(
    "vendor",
    "node_modules",
    ".git",
    ".env",
    ".env.backup",
    ".env.production",
    ".phpunit.result.cache",
    ".phpunit.cache",
    $zipName
)

Get-ChildItem -Path $rootPath | Where-Object { $excludeItems -notcontains $_.Name } | ForEach-Object {
    Copy-Item -Path $_.FullName -Destination $tempFolder -Recurse -Force
}

# Hapus berkas video mentah berukuran besar (>50 MB) dari arsip ZIP agar ukuran pengumpulan tetap ringan (<5 MB)
Get-ChildItem -Path $tempFolder -Recurse -File | Where-Object { $_.Length -gt 50MB } | Remove-Item -Force

# Bersihkan berkas sensitif, log runtime, database lokal, dan cache framework di dalam salinan sementara
$bootstrapCacheDir = Join-Path $tempFolder "bootstrap/cache"
if (Test-Path $bootstrapCacheDir) {
    Get-ChildItem -Path $bootstrapCacheDir -File | Where-Object { $_.Name -ne ".gitignore" } | Remove-Item -Force
}

$dbDir = Join-Path $tempFolder "database"
if (Test-Path $dbDir) {
    Get-ChildItem -Path $dbDir -File -Include "*.sqlite", "*.sqlite-journal", "*.sqlite-wal", "*.sqlite-shm" | Remove-Item -Force
}

# Pastikan folder storage bersih (hanya menyisakan struktur direktori kosong dengan .gitignore)
$storageDirs = @(
    "storage/app/private",
    "storage/app/public",
    "storage/framework/cache",
    "storage/framework/cache/data",
    "storage/framework/sessions",
    "storage/framework/testing",
    "storage/framework/views",
    "storage/logs"
)
foreach ($dir in $storageDirs) {
    $fullDir = Join-Path $tempFolder $dir
    if (Test-Path $fullDir) {
        Get-ChildItem -Path $fullDir -Force | Where-Object { $_.Name -ne ".gitignore" -and !$_.PSIsContainer } | Remove-Item -Force
        Get-ChildItem -Path $fullDir -Directory -Force | Where-Object { $_.Name -ne "data" } | Remove-Item -Recurse -Force
    } else {
        New-Item -ItemType Directory -Path $fullDir -Force | Out-Null
    }
    $gitignorePath = Join-Path $fullDir ".gitignore"
    if (!(Test-Path $gitignorePath)) {
        Set-Content -Path $gitignorePath -Value "*`n!.gitignore" -Encoding UTF8
    }
}

[System.IO.Compression.ZipFile]::CreateFromDirectory($tempFolder, $outputPath, [System.IO.Compression.CompressionLevel]::Optimal, $false)
Remove-Item -Recurse -Force $tempFolder

$fileSizeMB = [math]::Round((Get-Item $outputPath).Length / 1MB, 2)
Write-Host "===> Paket pengumpulan berhasil dibuat:" -ForegroundColor Green
Write-Host "Lokasi: $outputPath" -ForegroundColor Green
Write-Host "Ukuran: $fileSizeMB MB (Bersih tanpa vendor, .env, .git, dan cache)" -ForegroundColor Green
