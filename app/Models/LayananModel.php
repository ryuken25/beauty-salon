<?php
namespace App\Models;

class LayananModel extends BaseAppModel
{
    protected $table = 'layanan';
    protected $allowedFields = [
        'nama', 'kategori', 'deskripsi', 'durasi_menit', 'harga', 'ikon', 'is_active',
        'gambar', 'promo_persen', 'promo_deskripsi',
    ];
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    public function hasBookings(int $layananId): bool
    {
        return db_connect()->table('bookings')->where('layanan_id', $layananId)->countAllResults() > 0;
    }

    /** Decode kolom gambar JSON → array path bersih. Aman walau null/invalid. */
    public static function gambarList(array $l): array
    {
        $raw = $l['gambar'] ?? null;
        if (is_array($raw)) {
            return array_values(array_filter($raw, 'is_string'));
        }
        if (! is_string($raw) || $raw === '') {
            return [];
        }
        $arr = json_decode($raw, true);
        return is_array($arr) ? array_values(array_filter($arr, 'is_string')) : [];
    }

    /** Path cover (gambar pertama) atau null. */
    public static function cover(array $l): ?string
    {
        return self::gambarList($l)[0] ?? null;
    }

    /** True kalau layanan punya promo aktif. */
    public static function isPromo(array $l): bool
    {
        return isset($l['promo_persen']) && (int) $l['promo_persen'] > 0;
    }

    /** Harga final setelah promo (dibulatkan ke rupiah). */
    public static function hargaFinal(array $l): int
    {
        $h = (int) $l['harga'];
        if (! self::isPromo($l)) return $h;
        return (int) round($h * (100 - (int) $l['promo_persen']) / 100);
    }

    /** Normalisasi array path → string JSON siap simpan (atau null kalau kosong). */
    public static function encodeGambar(array $paths): ?string
    {
        $clean = array_values(array_filter(
            array_map('strval', $paths),
            static fn ($p) => $p !== ''
        ));
        return $clean ? json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
    }

    /** Listing aktif; promo tampil duluan, lalu urutan kategori → nama. */
    public function activeForListing(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('(promo_persen IS NOT NULL AND promo_persen > 0)', 'DESC', false)
            ->orderBy('kategori', 'ASC')
            ->orderBy('nama', 'ASC')
            ->find();
    }

    /** Layanan promo aktif (popup & strip). */
    public function promoAktif(int $limit = 12): array
    {
        return $this->where('is_active', 1)
            ->where('promo_persen >', 0)
            ->orderBy('promo_persen', 'DESC')
            ->limit($limit)
            ->find();
    }
}
