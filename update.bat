@echo off
setlocal EnableDelayedExpansion

echo === SW Beauty Salon - Update ^& Run ===
echo.
echo Script ini akan:
echo   1. Tarik update terbaru dari GitHub (force reset — file tracked
echo      di-overwrite, .env LOKAL TIDAK DISENTUH karena gitignored).
echo   2. Jalankan setup-windows.bat (composer install, bikin DB jika
echo      belum ada, migrate + seed, jalankan server).
echo.

REM ── 1. Pastikan ini repo git ────────────────────────────────
if not exist ".git" (
    echo [X] Folder ini bukan git repo. Pastikan kamu di dalam folder
    echo     beauty-salon yang sudah di-clone, bukan di parent folder.
    pause
    exit /b 1
)

REM ── 2. Cek apakah .env ada (untuk info; tidak di-overwrite) ──
if exist ".env" (
    echo [i] .env lokal terdeteksi - akan dipertahankan apa adanya.
) else (
    echo [i] .env belum ada - setup-windows.bat nanti yang akan menyalin
    echo     dari .env.localhost.
)
echo.

REM ── 3. Fetch + force reset ke origin/main ───────────────────
echo [1/2] Pull terbaru dari origin/main...
git fetch origin
if errorlevel 1 (
    echo [X] git fetch gagal. Cek koneksi internet atau status repo.
    pause
    exit /b 1
)
git reset --hard origin/main
if errorlevel 1 (
    echo [X] git reset gagal.
    pause
    exit /b 1
)
echo     OK - sekarang sinkron dengan origin/main.
echo.

REM ── 4. Jalankan setup-windows.bat ───────────────────────────
echo [2/2] Menjalankan setup-windows.bat...
call setup-windows.bat
endlocal
