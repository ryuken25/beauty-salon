<?php
namespace App\Models;

class LayananModel extends BaseAppModel
{
    /** Jumlah hari sejak created_at supaya layanan dianggap "baru". Ganti angka ini untuk mengubah window. */
    public const HARI_BARU = 7;

    protected $table = 'layanan';
    protected $allowedFields = [
        'nama', 'kategori', 'deskripsi', 'durasi_menit', 'harga', 'ikon', 'is_active',
        'gambar', 'promo_persen', 'promo_deskripsi', 'promo_mulai', 'promo_selesai',
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

    /**
     * True kalau layanan punya promo aktif HARI INI.
     * Aktif = promo_persen > 0 AND today ∈ [promo_mulai, promo_selesai]
     * (NULL di salah satu sisi = tak terbatas di sisi itu).
     */
    public static function isPromo(array $l, ?string $date = null): bool
    {
        if (empty($l['promo_persen']) || (int) $l['promo_persen'] <= 0) {
            return false;
        }
        $targetDate = $date ?? date('Y-m-d');
        if (! empty($l['promo_mulai']) && $targetDate < substr((string) $l['promo_mulai'], 0, 10)) {
            return false;
        }
        if (! empty($l['promo_selesai']) && $targetDate > substr((string) $l['promo_selesai'], 0, 10)) {
            return false;
        }
        return true;
    }

    /** Format "1 Jun – 30 Jun 2026" atau null kalau tidak ada rentang. */
    public static function promoRange(array $l): ?string
    {
        $start = ! empty($l['promo_mulai']) ? substr((string) $l['promo_mulai'], 0, 10) : null;
        $end = ! empty($l['promo_selesai']) ? substr((string) $l['promo_selesai'], 0, 10) : null;
        if (! $start && ! $end) return null;
        $bln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $fmt = static function (string $d) use ($bln): string {
            $ts = strtotime($d);
            return date('j', $ts) . ' ' . $bln[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
        };
        if ($start && $end) return $fmt($start) . ' – ' . $fmt($end);
        if ($start)        return 'Mulai ' . $fmt($start);
        return 'Sampai ' . $fmt($end);
    }

    /** Harga final setelah promo (dibulatkan ke rupiah). */
    public static function hargaFinal(array $l, ?string $date = null): int
    {
        $h = (int) $l['harga'];
        if (! self::isPromo($l, $date)) return $h;
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
        $today = date('Y-m-d');
        return $this->where('is_active', 1)
            ->orderBy("CASE WHEN promo_persen > 0 AND (promo_mulai IS NULL OR promo_mulai <= '{$today}') AND (promo_selesai IS NULL OR promo_selesai >= '{$today}') THEN 1 ELSE 0 END", 'DESC', false)
            ->orderBy('kategori', 'ASC')
            ->orderBy('nama', 'ASC')
            ->find();
    }

    /** Layanan promo aktif (popup & strip). */
    public function promoAktif(int $limit = 12): array
    {
        $today = date('Y-m-d');
        return $this->where('is_active', 1)
            ->where('promo_persen >', 0)
            ->groupStart()
                ->where('promo_mulai IS NULL')
                ->orWhere('promo_mulai <=', $today)
            ->groupEnd()
            ->groupStart()
                ->where('promo_selesai IS NULL')
                ->orWhere('promo_selesai >=', $today)
            ->groupEnd()
            ->orderBy('promo_persen', 'DESC')
            ->limit($limit)
            ->find();
    }

    /** Alias untuk promoAktif() sesuai spesifikasi. */
    public function getActivePromos(int $limit = 12): array
    {
        return $this->promoAktif($limit);
    }

    // ── Layanan Baru (berdasarkan created_at) ─────────────────────────────

    /**
     * True kalau layanan ditambahkan dalam N hari terakhir (inklusif).
     * created_at NULL/kosong/invalid → false (tidak dianggap baru).
     */
    public static function isBaru(array $l, ?int $hari = null): bool
    {
        $hari = $hari ?? self::HARI_BARU;
        $ca   = $l['created_at'] ?? null;
        if (! is_string($ca) || $ca === '') {
            return false;
        }
        $ts = strtotime($ca);
        if ($ts === false) {
            return false;
        }
        $batas = strtotime("-{$hari} days 00:00:00");
        return $ts >= $batas;
    }

    /**
     * Selisih hari dari created_at ke hari ini. Null kalau created_at kosong/invalid.
     * Berguna untuk label "ditambahkan X hari lalu".
     */
    public static function umurHariBaru(array $l): ?int
    {
        $ca = $l['created_at'] ?? null;
        if (! is_string($ca) || $ca === '') {
            return null;
        }
        $ts = strtotime($ca);
        if ($ts === false) {
            return null;
        }
        return (int) round((time() - $ts) / 86400);
    }

    /**
     * Query layanan baru yang aktif: created_at dalam N hari terakhir.
     * Aman walau created_at NULL (row tersebut tidak lolos WHERE).
     */
    public function baruAktif(int $limit = 12, ?int $hari = null): array
    {
        $hari = $hari ?? self::HARI_BARU;
        return $this->where('is_active', 1)
            ->where('created_at IS NOT NULL', null, false)
            ->where('created_at >=', date('Y-m-d 00:00:00', strtotime("-{$hari} days")))
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->find();
    }
}
