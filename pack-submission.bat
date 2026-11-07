@echo off
echo ========================================================
echo  Mempersiapkan Arsip Pengumpulan Bersih - AKSARA (Aplikasi Kegiatan Sekolah dan Akademik Terpadu)
echo ========================================================
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\pack-submission.ps1"
echo.
pause
