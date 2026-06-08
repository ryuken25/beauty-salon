@echo off
setlocal EnableDelayedExpansion

echo === SW Beauty Salon - Auto Setup (Windows) ===
echo.

REM ── 1. Cek PHP & Composer ────────────────────────────────────
where php >nul 2>nul
if errorlevel 1 (
    echo [X] PHP tidak ditemukan di PATH.
    echo     Pastikan Laragon/XAMPP terinstal dan PHP ada di Environment Variables.
    pause
    exit /b 1
)

where composer >nul 2>nul
if errorlevel 1 (
    echo [X] Composer tidak ditemukan di PATH.
    echo     Download dari https://getcomposer.org/download/
    pause
    exit /b 1
)

REM ── 2. Cari mysql.exe (PATH ^| Laragon ^| XAMPP) ──────────────
set "MYSQL_EXE="
where mysql >nul 2>nul
if not errorlevel 1 (
    set "MYSQL_EXE=mysql"
) else (
    if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe"
    if not defined MYSQL_EXE (
        for /d %%d in ("C:\laragon\bin\mysql\mysql-*") do (
            if exist "%%d\bin\mysql.exe" set "MYSQL_EXE=%%d\bin\mysql.exe"
        )
    )
    if not defined MYSQL_EXE if exist "D:\xampp\mysql\bin\mysql.exe" set "MYSQL_EXE=D:\xampp\mysql\bin\mysql.exe"
)

if defined MYSQL_EXE (
    echo [i] mysql ditemukan: !MYSQL_EXE!
) else (
    echo [!] mysql CLI tidak ketemu otomatis di PATH/Laragon/XAMPP.
    echo     Skrip akan coba lanjut, tapi pembuatan database mungkin gagal.
    echo     Pastikan MySQL nyala ^(Laragon: Start All; XAMPP: Start MySQL^).
    echo     Tekan ENTER untuk lanjut atau Ctrl+C untuk batal.
    pause >nul
)

REM ── 3. composer install (retry untuk lock OneDrive/antivirus) ─
echo.
echo [1/5] composer install...
set "COMPOSER_OK="
for /l %%i in (1,1,3) do (
    if not defined COMPOSER_OK (
        if %%i gtr 1 (
            echo     [!] Percobaan %%i/3 ^(coba ulang setelah jeda^)...
            timeout /t 3 /nobreak >nul
        )
        call composer install --no-interaction
        if not errorlevel 1 set "COMPOSER_OK=1"
    )
)
if not defined COMPOSER_OK (
    echo.
    echo [X] composer install gagal setelah 3 percobaan.
    echo     Error "Resource temporarily unavailable" biasanya file vendor
    echo     terkunci oleh OneDrive atau antivirus. Solusi:
    echo       1. PINDAHKAN folder ini KE LUAR Documents/OneDrive,
    echo          misal: C:\laragon\www\beauty-salon  ^(paling ampuh^).
    echo       2. Atau pause sync OneDrive ^(klik ikon OneDrive ^> Pause^),
    echo          lalu jalankan ulang setup-windows.bat.
    echo       3. Atau kecualikan folder vendor dari scan Windows Defender.
    pause
    exit /b 1
)

REM ── 4. Siapkan .env ─────────────────────────────────────────
echo.
echo [2/5] Menyiapkan file .env...
if not exist .env (
    copy .env.localhost .env >nul
    echo     .env disalin dari .env.localhost.
) else (
    echo     .env sudah ada - dilewati.
)

REM ── 5. Bikin database (coba root tanpa password, lalu password root) ─
echo.
echo [3/5] Membuat database sw_beauty_salon...
set "DB_CREATED="
if defined MYSQL_EXE (
    "!MYSQL_EXE!" -u root -e "CREATE DATABASE IF NOT EXISTS sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>nul
    if not errorlevel 1 (
        set "DB_CREATED=1"
        echo     OK ^(root tanpa password^).
    ) else (
        "!MYSQL_EXE!" -u root -proot -e "CREATE DATABASE IF NOT EXISTS sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>nul
        if not errorlevel 1 (
            set "DB_CREATED=1"
            echo     OK ^(root password 'root' - edit .env: database.default.password = root^).
        )
    )
)
if not defined DB_CREATED (
    echo.
    echo [X] Gagal bikin database otomatis. Bikin manual lewat phpMyAdmin:
    echo     1. Buka http://localhost/phpmyadmin
    echo     2. Tab "Databases" - nama: sw_beauty_salon - collation: utf8mb4_unicode_ci - klik Create
    echo     3. Sesudah dibikin, jalankan ulang setup-windows.bat
    echo.
    pause
    exit /b 1
)

REM ── 6. Migrate + seed ───────────────────────────────────────
echo.
echo [4/5] Migrate + seed ^(membuat tabel + data demo^)...
call php spark migrate
if errorlevel 1 (
    echo.
    echo [X] Migrate gagal. Cek .env - database.default.username/password
    echo     harus cocok dengan MySQL Anda.
    pause
    exit /b 1
)
call php spark db:seed SalonSeeder
if errorlevel 1 (
    echo [!] db:seed gagal - tapi tabel sudah ada. Coba ulang manual:
    echo       php spark db:seed SalonSeeder
)

REM ── 7. Info + jalankan server ───────────────────────────────
echo.
echo [5/5] Menjalankan server di http://localhost:8080
echo     Akun demo:
echo       Pemilik:   owner@swbeautysalon.local / Password123! ^(di /admin/login^)
echo       Admin:     admin@swbeautysalon.local / Password123! ^(di /admin/login^)
echo       Pelanggan: WA 6281338109102          / Password123! ^(di /login^)
echo.
echo [i] Notifikasi email ^(opsional^): buka .env, isi email.SMTPUser/fromEmail
echo     dengan akun Gmail salon + email.SMTPPass dengan Gmail App Password
echo     ^(16 huruf, https://myaccount.google.com/apppasswords^).
echo     Tanpa ini, booking tetap jalan, hanya email yang nonaktif.
echo.
echo [i] Untuk reminder + auto-cancel di produksi, jadwalkan tiap 5 menit:
echo       php spark bookings:auto-cancel
echo       php spark bookings:send-reminders
echo.
echo     Tutup server dengan Ctrl+C kapan saja.
echo.
php spark serve
endlocal
