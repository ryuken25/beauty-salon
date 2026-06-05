#!/usr/bin/env bash
# SW Beauty Salon — Update & Run (Mac / Linux / Git Bash)
#
# Tarik update terbaru dari GitHub (force reset, .env lokal aman karena
# gitignored), lalu setup composer / .env / database / migrate / serve.
#
# Pakai: bash update.sh   atau   ./update.sh   (chmod +x update.sh sekali)

set -e

echo "=== SW Beauty Salon - Update & Run ==="
echo

if [ ! -d ".git" ]; then
    echo "[X] Folder ini bukan git repo. Masuk dulu ke folder beauty-salon."
    exit 1
fi

if [ -f ".env" ]; then
    echo "[i] .env lokal terdeteksi — akan dipertahankan apa adanya."
else
    echo "[i] .env belum ada — akan disalin dari .env.localhost nanti."
fi
echo

# ── 1. Pull terbaru ──────────────────────────────────────────
echo "[1/6] Pull terbaru dari origin/main..."
git fetch origin
git reset --hard origin/main
echo "    OK — sinkron dengan origin/main."
echo

# ── 2. Cari mysql (PATH / Laragon / XAMPP / Homebrew) ────────
MYSQL_EXE=""
if command -v mysql >/dev/null 2>&1; then
    MYSQL_EXE="mysql"
elif [ -x "/c/xampp/mysql/bin/mysql.exe" ]; then
    MYSQL_EXE="/c/xampp/mysql/bin/mysql.exe"
elif [ -x "/opt/homebrew/bin/mysql" ]; then
    MYSQL_EXE="/opt/homebrew/bin/mysql"
elif [ -x "/usr/local/bin/mysql" ]; then
    MYSQL_EXE="/usr/local/bin/mysql"
else
    # Laragon (Git Bash on Windows)
    for d in /c/laragon/bin/mysql/mysql-*/bin/mysql.exe; do
        if [ -x "$d" ]; then MYSQL_EXE="$d"; break; fi
    done
fi

if [ -n "$MYSQL_EXE" ]; then
    echo "[i] mysql ditemukan: $MYSQL_EXE"
else
    echo "[!] mysql CLI tidak ketemu otomatis. Pembuatan database mungkin gagal."
    echo "    Pastikan MySQL nyala dan, kalau perlu, bikin database manual:"
    echo "      CREATE DATABASE sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    read -r -p "Tekan ENTER untuk lanjut atau Ctrl+C untuk batal."
fi
echo

# ── 3. composer install ──────────────────────────────────────
echo "[2/6] composer install..."
composer install --no-interaction
echo

# ── 4. Siapkan .env (skip kalau sudah ada) ───────────────────
echo "[3/6] Menyiapkan .env..."
if [ ! -f ".env" ]; then
    cp .env.localhost .env
    echo "    .env disalin dari .env.localhost."
else
    echo "    .env sudah ada — dilewati (TIDAK DIGANTI)."
fi
echo

# ── 5. Bikin database ────────────────────────────────────────
echo "[4/6] Membuat database sw_beauty_salon..."
DB_CREATED=0
if [ -n "$MYSQL_EXE" ]; then
    if "$MYSQL_EXE" -u root -e "CREATE DATABASE IF NOT EXISTS sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >/dev/null 2>&1; then
        echo "    OK (root tanpa password)."
        DB_CREATED=1
    elif "$MYSQL_EXE" -u root -proot -e "CREATE DATABASE IF NOT EXISTS sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >/dev/null 2>&1; then
        echo "    OK (root password 'root' — edit .env: database.default.password = root)."
        DB_CREATED=1
    fi
fi
if [ "$DB_CREATED" -eq 0 ]; then
    echo "[X] Gagal bikin database otomatis. Bikin manual:"
    echo "    mysql -u root -p -e \"CREATE DATABASE sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
    echo "    lalu jalankan ulang ./update.sh"
    exit 1
fi
echo

# ── 6. Migrate + seed ────────────────────────────────────────
echo "[5/6] Migrate + seed..."
php spark migrate
php spark db:seed SalonSeeder || echo "[!] db:seed gagal — bisa coba ulang: php spark db:seed SalonSeeder"
echo

# ── 7. Server ────────────────────────────────────────────────
echo "[6/6] Menjalankan server di http://localhost:8080"
echo "    Akun demo:"
echo "      Pemilik:   owner@swbeautysalon.local / Password123! (di /admin/login)"
echo "      Admin:     admin@swbeautysalon.local / Password123! (di /admin/login)"
echo "      Pelanggan: WA 6281338109102          / Password123! (di /login)"
echo
echo "[i] Notifikasi email: edit .env, isi email.SMTPUser/fromEmail + email.SMTPPass"
echo "    (App Password 16 huruf dari myaccount.google.com/apppasswords)."
echo
echo "    Tutup server dengan Ctrl+C kapan saja."
echo
php spark serve
