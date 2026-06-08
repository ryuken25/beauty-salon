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

function Find-MysqldExe {
    $candidates = @(
        'C:\xampp\mysql\bin\mysqld.exe',
        'D:\xampp\mysql\bin\mysqld.exe'
    )
    foreach ($p in $candidates) {
        if (Test-Path $p) { return $p }
    }
    $laragon = Get-ChildItem -Path 'C:\laragon\bin\mysql' -Directory -ErrorAction SilentlyContinue
    foreach ($d in $laragon) {
        $p = Join-Path $d.FullName 'bin\mysqld.exe'
        if (Test-Path $p) { return $p }
    }
    return $null
}

function Start-Mysqld($mysqldExe) {
    $dir = Split-Path $mysqldExe -Parent
    $ini = Join-Path $dir 'my.ini'
    $args = if (Test-Path $ini) { @("--defaults-file=$ini", '--standalone') } else { @('--standalone') }
    try {
        Start-Process -FilePath $mysqldExe -ArgumentList $args -WindowStyle Minimized | Out-Null
        return $true
    } catch {
        return $false
    }
}

$script:DbError = ''
$script:DbPass  = ''
function Try-CreateDatabase($mysqlExe) {
    foreach ($pwArgs in @(@('-u','root'), @('-u','root','-proot'))) {
        $cmdArgs = $pwArgs + @('-e', "CREATE DATABASE IF NOT EXISTS sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")
        try {
            $out = & $mysqlExe @cmdArgs 2>&1
            if ($LASTEXITCODE -eq 0) {
                if ($pwArgs.Count -eq 4) { $script:DbPass = 'root'; return "(root password 'root')" }
                $script:DbPass = ''; return "(root tanpa password)"
            }
            $script:DbError = ($out | Out-String).Trim()
        } catch {
            $script:DbError = $_.Exception.Message
        }
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
# Kalau gagal, coba nyalakan mysqld otomatis lalu ulang
if (-not $dbCreated) {
    $mysqldExe = Find-MysqldExe
    if ($mysqldExe) {
        Write-Host '    [i] MySQL belum nyala - menyalakan otomatis...' -ForegroundColor Yellow
        if (Start-Mysqld $mysqldExe) {
            Write-Host '    [i] Menunggu MySQL siap (maks ~40 detik)...'
            for ($w = 0; $w -lt 20 -and -not $dbCreated; $w++) {
                Start-Sleep -Seconds 2
                $note = Try-CreateDatabase $mysqlExe
                if ($note) {
                    $dbCreated = $true
                    Write-Host "    OK $note - MySQL dinyalakan otomatis."
                }
            }
        }
    }
}
if (-not $dbCreated) {
    Write-Host ''
    Write-Host '[X] Gagal bikin database otomatis. Pesan asli dari MySQL:' -ForegroundColor Red
    Write-Host '    ----------------------------------------------------------'
    if ($script:DbError) { Write-Host ("    " + ($script:DbError -replace "`n", "`n    ")) }
    Write-Host '    ----------------------------------------------------------'
    Write-Host ''
    if ($script:DbError -match '2002|2003|refused|10061|can.t connect') {
        Write-Host '[!] MySQL tidak bisa dinyalakan otomatis. Buka XAMPP Control Panel,' -ForegroundColor Yellow
        Write-Host '    klik Start pada baris "MySQL" sampai hijau (kalau gagal start,' -ForegroundColor Yellow
        Write-Host '    biasanya port 3306 dipakai aplikasi lain), lalu jalankan ulang.' -ForegroundColor Yellow
    } elseif ($script:DbError -match '1045|Access denied') {
        Write-Host '[!] Password root MySQL bukan kosong/''root''. Edit .env:' -ForegroundColor Yellow
        Write-Host '    database.default.password sesuai password MySQL kamu.' -ForegroundColor Yellow
    } else {
        Write-Host '[!] Pastikan MySQL nyala di XAMPP (klik Start pada "MySQL").' -ForegroundColor Yellow
    }
    Write-Host ''
    Write-Host '    Alternatif - bikin database manual lewat phpMyAdmin:'
    Write-Host '    1. Pastikan Apache + MySQL di XAMPP sudah Start (hijau).'
    Write-Host '    2. Buka http://localhost/phpmyadmin'
    Write-Host '    3. Tab "Databases" - nama: sw_beauty_salon - collation: utf8mb4_unicode_ci - klik Create'
    Write-Host '    4. Sesudah dibikin, jalankan ulang .\setup-windows.ps1'
    exit 1
}

# Sinkronkan .env dgn kredensial yg bekerja (host 127.0.0.1) sebelum migrate,
# supaya mysqli tidak gagal via named-pipe 'localhost'.
php (Join-Path $PSScriptRoot 'scripts\patch_env_db.php') $script:DbPass

Write-Host ''
Write-Host '[4/5] Migrate + seed...' -ForegroundColor Cyan
# Pastikan folder writable yang dibutuhkan CI4 ada (cache/session/logs/...)
foreach ($d in 'cache','logs','session','uploads','debugbar') {
    $p = Join-Path 'writable' $d
    if (-not (Test-Path $p)) { New-Item -ItemType Directory -Path $p | Out-Null }
}
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
