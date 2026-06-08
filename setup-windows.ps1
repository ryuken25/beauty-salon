$ErrorActionPreference = 'Stop'

function Require-Command($name, $hint) {
    if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
        Write-Host "[X] '$name' tidak ditemukan di PATH. $hint" -ForegroundColor Red
        exit 1
    }
}

function Find-MysqlExe {
    $cmd = Get-Command 'mysql' -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    $candidates = @(
        'C:\xampp\mysql\bin\mysql.exe',
        'D:\xampp\mysql\bin\mysql.exe'
    )
    foreach ($p in $candidates) {
        if (Test-Path $p) { return $p }
    }
    $laragon = Get-ChildItem -Path 'C:\laragon\bin\mysql' -Directory -ErrorAction SilentlyContinue
    foreach ($d in $laragon) {
        $p = Join-Path $d.FullName 'bin\mysql.exe'
        if (Test-Path $p) { return $p }
    }
    return $null
}

function Try-CreateDatabase($mysqlExe) {
    foreach ($pwArgs in @(@('-u','root'), @('-u','root','-proot'))) {
        $args = $pwArgs + @('-e', "CREATE DATABASE IF NOT EXISTS sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")
        try {
            & $mysqlExe @args 2>$null
            if ($LASTEXITCODE -eq 0) {
                $note = if ($pwArgs.Count -eq 4) { "(root password 'root' — edit .env: database.default.password = root)" } else { "(root tanpa password)" }
                return $note
            }
        } catch {}
    }
    return $null
}

Write-Host '=== SW Beauty Salon - Auto Setup (Windows / PowerShell) ===' -ForegroundColor Cyan
Write-Host ''

Require-Command 'php' 'Install Laragon atau XAMPP, lalu pastikan PHP di PATH.'
Require-Command 'composer' 'Download dari https://getcomposer.org/download/.'

$mysqlExe = Find-MysqlExe
if ($mysqlExe) {
    Write-Host "[i] mysql ditemukan: $mysqlExe"
} else {
    Write-Host "[!] mysql CLI tidak ketemu otomatis di PATH/Laragon/XAMPP." -ForegroundColor Yellow
    Write-Host "    Pastikan MySQL nyala (Laragon: Start All; XAMPP: Start MySQL)." -ForegroundColor Yellow
    Read-Host 'Tekan ENTER untuk lanjut, atau Ctrl+C untuk batal'
}

Write-Host ''
Write-Host '[1/5] composer install...' -ForegroundColor Cyan
$composerOk = $false
for ($i = 1; $i -le 3; $i++) {
    if ($i -gt 1) {
        Write-Host "    [!] Percobaan $i/3 (coba ulang setelah jeda)..." -ForegroundColor Yellow
        Start-Sleep -Seconds 3
    }
    composer install --no-interaction
    if ($LASTEXITCODE -eq 0) { $composerOk = $true; break }
}
if (-not $composerOk) {
    Write-Host ''
    Write-Host '[X] composer install gagal setelah 3 percobaan.' -ForegroundColor Red
    Write-Host '    Error "Resource temporarily unavailable" biasanya file vendor' -ForegroundColor Red
    Write-Host '    terkunci oleh OneDrive atau antivirus. Solusi:' -ForegroundColor Red
    Write-Host '      1. PINDAHKAN folder ini KE LUAR Documents/OneDrive,'
    Write-Host '         misal: C:\laragon\www\beauty-salon  (paling ampuh).'
    Write-Host '      2. Atau pause sync OneDrive (klik ikon OneDrive > Pause),'
    Write-Host '         lalu jalankan ulang .\setup-windows.ps1.'
    Write-Host '      3. Atau kecualikan folder vendor dari scan Windows Defender.'
    exit 1
}

Write-Host ''
Write-Host '[2/5] Menyiapkan .env...' -ForegroundColor Cyan
if (-not (Test-Path .env)) {
    Copy-Item .env.localhost .env
    Write-Host '    .env disalin dari .env.localhost.'
} else {
    Write-Host '    .env sudah ada - dilewati.'
}

Write-Host ''
Write-Host '[3/5] Membuat database sw_beauty_salon...' -ForegroundColor Cyan
$dbCreated = $false
if ($mysqlExe) {
    $note = Try-CreateDatabase $mysqlExe
    if ($note) {
        $dbCreated = $true
        Write-Host "    OK $note"
    }
}
if (-not $dbCreated) {
    Write-Host ''
    Write-Host '[X] Gagal bikin database otomatis. Bikin manual lewat phpMyAdmin:' -ForegroundColor Red
    Write-Host '    1. Buka http://localhost/phpmyadmin'
    Write-Host '    2. Tab "Databases" - nama: sw_beauty_salon - collation: utf8mb4_unicode_ci - klik Create'
    Write-Host '    3. Sesudah dibikin, jalankan ulang .\setup-windows.ps1'
    exit 1
}

Write-Host ''
Write-Host '[4/5] Migrate + seed...' -ForegroundColor Cyan
php spark migrate
if ($LASTEXITCODE -ne 0) {
    Write-Host '[X] Migrate gagal. Cek .env - database.default.username/password harus cocok.' -ForegroundColor Red
    exit 1
}
php spark db:seed SalonSeeder
if ($LASTEXITCODE -ne 0) {
    Write-Host '[!] db:seed gagal - tabel sudah ada. Coba ulang: php spark db:seed SalonSeeder' -ForegroundColor Yellow
}

Write-Host ''
Write-Host '[5/5] Menjalankan server di http://localhost:8080' -ForegroundColor Green
Write-Host '    Akun demo:'
Write-Host '      Pemilik:   owner@swbeautysalon.local / Password123! (di /admin/login)'
Write-Host '      Admin:     admin@swbeautysalon.local / Password123! (di /admin/login)'
Write-Host '      Pelanggan: WA 6281338109102          / Password123! (di /login)'
Write-Host ''
Write-Host '[i] Notifikasi email (opsional): buka .env, isi email.SMTPUser/fromEmail' -ForegroundColor Yellow
Write-Host '    dengan akun Gmail salon + email.SMTPPass dengan Gmail App Password (16 huruf,' -ForegroundColor Yellow
Write-Host '    https://myaccount.google.com/apppasswords). Tanpa ini, booking tetap jalan,' -ForegroundColor Yellow
Write-Host '    hanya email yang nonaktif.' -ForegroundColor Yellow
Write-Host ''
Write-Host '[i] Reminder + auto-cancel di produksi, jadwalkan tiap 5 menit:' -ForegroundColor Yellow
Write-Host '      php spark bookings:auto-cancel' -ForegroundColor Yellow
Write-Host '      php spark bookings:send-reminders' -ForegroundColor Yellow
Write-Host ''
php spark serve
