<?php
/**
 * Downloader foto seed layanan — diisi ke public/uploads/layanan/seed/.
 *
 * Sumber: Lorem Picsum (https://picsum.photos) — Unsplash CC0 photo set,
 * deterministik per `seed=<slug>`. Tidak bertema, tapi unik & legal tanpa
 * atribusi. Kalau mau foto bertema (Pexels/Pixabay/Unsplash search), timpa
 * file di folder ini secara manual lalu jalankan `php spark db:seed
 * SalonSeeder` ulang.
 *
 * Hasil: 800×600 JPEG per slug. Untuk layanan galeri (⭐) ada 3 foto:
 *   <slug>.jpg, <slug>-2.jpg, <slug>-3.jpg.
 *
 * Pakai:  php scripts/download_seed_photos.php
 */

$ROOT = dirname(__DIR__);
$OUT  = $ROOT . '/public/uploads/layanan/seed';
@mkdir($OUT, 0775, true);

// Daftar slug — harus sama persis dengan slugify() di SalonSeeder.
$cover = [
    'nail-art',
    'manicure-pedicure',
    'callus-removal',
    'eyelash-extension',
    'shaping-alis',
    'sulam-alis',
    'sulam-bibir',
    'ipl-treatment',
    'waxing-detox-underarm',
    'wax-kaki-tangan',
    'facial',
    'keramas',
    'masker-bilas',
    'catok-styling',
    'masker-steam',
    'creambath',
    'hair-spa',
    'smoothing',
    'blow-permanent',
    'treatment-anti-ketombe',
    'treatment-rambut-rontok',
    'hair-filler-keratin',
    'hair-color',
    'make-up',
];
$gallery = ['nail-art', 'eyelash-extension', 'sulam-alis', 'facial', 'creambath', 'hair-spa', 'make-up'];

function fetchTo(string $url, string $dest): bool
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'sw-beauty-salon-seeder/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || ! $body || strlen($body) < 5_000) {
        return false;
    }
    return file_put_contents($dest, $body) !== false;
}

function picsumUrl(string $seed): string
{
    return 'https://picsum.photos/seed/' . rawurlencode($seed) . '/800/600';
}

$total = 0;
$ok = 0;
$failed = [];

foreach ($cover as $slug) {
    $dest = $OUT . '/' . $slug . '.jpg';
    $total++;
    if (is_file($dest) && filesize($dest) > 5_000) {
        echo "  skip {$slug}.jpg (already exists)\n";
        $ok++;
        continue;
    }
    if (fetchTo(picsumUrl($slug), $dest)) {
        echo "  ok   {$slug}.jpg\n";
        $ok++;
    } else {
        echo "  FAIL {$slug}.jpg\n";
        $failed[] = $slug . '.jpg';
    }
}

foreach ($gallery as $slug) {
    foreach ([2, 3] as $i) {
        $fname = $slug . '-' . $i . '.jpg';
        $dest = $OUT . '/' . $fname;
        $total++;
        if (is_file($dest) && filesize($dest) > 5_000) {
            echo "  skip {$fname} (already exists)\n";
            $ok++;
            continue;
        }
        if (fetchTo(picsumUrl($slug . '-' . $i), $dest)) {
            echo "  ok   {$fname}\n";
            $ok++;
        } else {
            echo "  FAIL {$fname}\n";
            $failed[] = $fname;
        }
    }
}

echo "\n";
echo "Total: {$ok}/{$total} berhasil.\n";
if ($failed) {
    echo "Gagal: " . implode(', ', $failed) . "\n";
    echo "(Yang gagal akan auto-fallback ke placeholder GD saat seeder jalan.)\n";
}
