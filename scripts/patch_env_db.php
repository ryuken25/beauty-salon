<?php
/**
 * Sinkronkan kredensial database di .env dengan yang terbukti bekerja saat setup.
 *
 * Dipanggil otomatis oleh setup-windows.bat / setup-windows.ps1 setelah database
 * berhasil dibuat. Tujuannya: hindari "Migrate gagal" gara-gara .env tidak cocok
 * (mis. hostname 'localhost' yang bikin mysqli pakai named-pipe alih-alih TCP,
 * atau password root yang berbeda).
 *
 * Penggunaan: php scripts/patch_env_db.php [password_root]
 *   - argumen 1 = password root MySQL ('' kalau tanpa password).
 */

$envPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '.env';
if (! is_file($envPath)) {
    fwrite(STDERR, "[patch_env_db] .env tidak ditemukan di " . $envPath . "\n");
    exit(1);
}

$password = $argv[1] ?? '';

$contents = file_get_contents($envPath);
if ($contents === false) {
    fwrite(STDERR, "[patch_env_db] gagal membaca .env\n");
    exit(1);
}

/**
 * Set/ganti satu key di .env. Kalau baris ada (termasuk yang dikomentari '#'),
 * ditimpa; kalau tidak ada, ditambahkan di akhir.
 */
$setKey = static function (string $text, string $key, string $value): string {
    $line    = $key . ' = ' . $value;
    $pattern = '/^\s*#?\s*' . preg_quote($key, '/') . '\s*=.*$/m';
    if (preg_match($pattern, $text)) {
        return preg_replace($pattern, $line, $text, 1);
    }
    return rtrim($text, "\r\n") . "\n" . $line . "\n";
};

$contents = $setKey($contents, 'database.default.hostname', '127.0.0.1');
$contents = $setKey($contents, 'database.default.database', 'sw_beauty_salon');
$contents = $setKey($contents, 'database.default.username', 'root');
$contents = $setKey($contents, 'database.default.password', $password);
$contents = $setKey($contents, 'database.default.port', '3306');
$contents = $setKey($contents, 'database.default.DBDriver', 'MySQLi');

if (file_put_contents($envPath, $contents) === false) {
    fwrite(STDERR, "[patch_env_db] gagal menulis .env\n");
    exit(1);
}

echo "[.env] kredensial database disinkronkan (host 127.0.0.1, user root, db sw_beauty_salon).\n";
