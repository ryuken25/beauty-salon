$ErrorActionPreference = 'Stop'

function Require-Command($name, $hint) {
    if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
        Write-Host "[X] '$name' tidak ditemukan di PATH. $hint" -ForegroundColor Red
        exit 1
    }
}

Write-Host '=== SW Beauty Salon - Auto Setup (Windows / PowerShell) ===' -ForegroundColor Cyan
Write-Host ''

Require-Command 'php' 'Install Laragon atau XAMPP, lalu pastikan PHP di PATH.'
Require-Command 'composer' 'Download dari https://getcomposer.org/download/.'

if (-not (Get-Command 'mysql' -ErrorAction SilentlyContinue)) {
    Write-Host "[!] 'mysql' CLI tidak ditemukan — pastikan MySQL/MariaDB sudah jalan di Laragon/XAMPP." -ForegroundColor Yellow
    Read-Host 'Tekan ENTER untuk lanjut, atau Ctrl+C untuk batal'
}

Write-Host '[1/5] composer install...' -ForegroundColor Cyan
composer install --no-interaction
if ($LASTEXITCODE -ne 0) { Write-Host '[X] composer install gagal.' -ForegroundColor Red; exit 1 }

Write-Host ''
Write-Host '[2/5] Menyiapkan .env...' -ForegroundColor Cyan
if (-not (Test-Path .env)) {
    Copy-Item .env.localhost .env
    Write-Host '    .env disalin dari .env.localhost.'
} else {
    Write-Host '    .env sudah ada — dilewati.'
}

Write-Host ''
Write-Host '[3/5] Membuat database sw_beauty_salon...' -ForegroundColor Cyan
try {
    & mysql -u root -e "CREATE DATABASE IF NOT EXISTS sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>$null
} catch {
    Write-Host "[!] mysql CLI gagal — buat manual lewat phpMyAdmin (nama: sw_beauty_salon)." -ForegroundColor Yellow
}

Write-Host ''
Write-Host '[4/5] Migrate + seed...' -ForegroundColor Cyan
php spark migrate
if ($LASTEXITCODE -ne 0) { Write-Host '[X] Migrate gagal. Cek .env + status MySQL.' -ForegroundColor Red; exit 1 }
php spark db:seed SalonSeeder

Write-Host ''
Write-Host '[5/5] Menjalankan server di http://localhost:8080' -ForegroundColor Green
Write-Host '    Akun demo:'
Write-Host '      Pemilik:   owner@swbeautysalon.local / Password123!'
Write-Host '      Admin:     admin@swbeautysalon.local / Password123!'
Write-Host '      Pelanggan: nomor WA 6281338109102 / Password123!'
Write-Host ''
php spark serve
