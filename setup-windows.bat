@echo off
setlocal EnableDelayedExpansion

echo === SW Beauty Salon - Auto Setup (Windows) ===
echo.

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

where mysql >nul 2>nul
if errorlevel 1 (
    echo [!] Peringatan: 'mysql' CLI tidak ditemukan. Pastikan MySQL/MariaDB
    echo     sudah jalan ^(Laragon: tombol Start All; XAMPP: Start MySQL^),
    echo     lalu lanjutkan dengan menekan ENTER. Jika belum, batalkan dengan Ctrl+C.
    pause
)

echo [1/5] composer install...
call composer install --no-interaction
if errorlevel 1 (
    echo [X] composer install gagal.
    pause
    exit /b 1
)

echo.
echo [2/5] Menyiapkan file .env...
if not exist .env (
    copy .env.localhost .env >nul
    echo     .env disalin dari .env.localhost.
) else (
    echo     .env sudah ada — dilewati.
)

echo.
echo [3/5] Membuat database sw_beauty_salon ^(akan dilewati jika sudah ada^)...
mysql -u root -e "CREATE DATABASE IF NOT EXISTS sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
if errorlevel 1 (
    echo [!] mysql CLI gagal — pastikan MySQL nyala dan user root tidak butuh password,
    echo     atau buat manual lewat phpMyAdmin: database "sw_beauty_salon".
    echo     Skrip akan lanjut; kalau migrate gagal di langkah berikutnya, perbaiki dulu.
)

echo.
echo [4/5] Migrate + seed ^(membuat tabel + data demo^)...
call php spark migrate
if errorlevel 1 (
    echo [X] Migrate gagal. Pastikan MySQL jalan dan kredensial di .env benar.
    pause
    exit /b 1
)
call php spark db:seed SalonSeeder

echo.
echo [5/5] Menjalankan server di http://localhost:8080
echo     Akun demo:
echo       Pemilik:   owner@swbeautysalon.local / Password123!
echo       Admin:     admin@swbeautysalon.local / Password123!
echo       Pelanggan: WA 6281338109102 / Password123! (email: winayagatar@gmail.com)
echo.
echo [i] Notifikasi email (opsional): buka .env, isi email.SMTPUser/fromEmail
echo     dengan akun Gmail salon + email.SMTPPass dengan Gmail App Password
echo     (16 huruf, https://myaccount.google.com/apppasswords).
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
