<?php

/**
 * Helper WhatsApp — arah pelanggan → admin.
 *
 * Dipakai oleh sisi publik/pelanggan untuk menampilkan tombol dan nomor
 * WhatsApp admin. Tetap manual lewat tautan wa.me, tanpa API berbayar.
 *
 * Jangan dipakai untuk alur admin → pelanggan (template pesan, tombol salin,
 * penanda "sudah dikirim") — itu tetap milik App\Services\WhatsAppTemplateService.
 *
 * wa_normalize(), wa_link(), wa_display(), dan wa_message() adalah fungsi
 * murni: hanya mengolah argumen, tidak menyentuh database dan tidak mencetak
 * apa pun. Hanya wa_admin_number()/wa_admin_link()/wa_admin_display() yang
 * membaca sumber konfigurasi.
 */

if (! function_exists('wa_normalize')) {
    /**
     * Ubah nomor apa pun jadi format wa.me: 62 diikuti nomor tanpa angka 0 depan.
     *
     * Spasi, tanda hubung, kurung, titik, dan tanda + dibuang. Awalan 0 dan +62
     * sama-sama menjadi 62. Mengembalikan null kalau hasilnya bukan nomor
     * seluler Indonesia yang masuk akal (62 + 8 + 7..13 digit).
     */
    function wa_normalize(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        // Buang semua karakter non-digit sekaligus: spasi, -, (, ), titik, dan +.
        $digits = preg_replace('/\D+/', '', $number) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return preg_match('/^628\d{7,13}$/', $digits) === 1 ? $digits : null;
    }
}

if (! function_exists('wa_link')) {
    /**
     * Bentuk tautan wa.me lengkap dengan pesan prefilled.
     * Mengembalikan null kalau nomornya tidak valid, supaya pemanggil bisa
     * memilih untuk tidak merender tautan sama sekali.
     */
    function wa_link(?string $number, string $message = ''): ?string
    {
        $normalized = wa_normalize($number);
        if ($normalized === null) {
            return null;
        }

        $url = 'https://wa.me/' . $normalized;
        if ($message !== '') {
            $url .= '?text=' . rawurlencode($message);
        }

        return $url;
    }
}

if (! function_exists('wa_display')) {
    /**
     * Format nomor untuk ditampilkan ke pelanggan: 6287862183074 → 0878-6218-3074.
     * Yang dipakai di atribut href tetap hasil wa_normalize(), bukan ini.
     */
    function wa_display(?string $number): ?string
    {
        $normalized = wa_normalize($number);
        if ($normalized === null) {
            return null;
        }

        $local = '0' . substr($normalized, 2);
        $parts = array_filter([
            substr($local, 0, 4),
            substr($local, 4, 4),
            substr($local, 8),
        ], static fn (string $part): bool => $part !== '');

        return implode('-', $parts);
    }
}

if (! function_exists('wa_message')) {
    /**
     * Susun pesan prefilled sesuai halaman yang sedang dibuka.
     *
     * Konteks yang dikenal:
     *   'layanan' — butuh $data['layanan']
     *   'booking' — butuh $data['kode'], opsional $data['nama']
     * Konteks lain (atau data kurang lengkap) jatuh ke pesan umum.
     */
    function wa_message(string $context = 'umum', array $data = []): string
    {
        $umum = 'Halo Admin SW Beauty Salon, saya ingin bertanya tentang layanan salon.';

        if ($context === 'layanan') {
            $layanan = trim((string) ($data['layanan'] ?? ''));

            return $layanan === ''
                ? $umum
                : "Halo Admin SW Beauty Salon, saya ingin bertanya tentang layanan {$layanan}.";
        }

        if ($context === 'booking') {
            $kode = trim((string) ($data['kode'] ?? ''));
            $nama = trim((string) ($data['nama'] ?? ''));

            if ($kode === '') {
                return $umum;
            }

            return $nama === ''
                ? "Halo Admin, saya ingin menanyakan booking dengan kode {$kode}."
                : "Halo Admin, saya ingin menanyakan booking dengan kode {$kode} atas nama {$nama}.";
        }

        return $umum;
    }
}

if (! function_exists('wa_admin_number')) {
    /**
     * Nomor WhatsApp admin dalam format wa.me, atau null kalau belum diisi.
     *
     * Urutan: setting `wa_admin` di database → salon.waAdmin di .env →
     * default di app/Config/Salon.php. Pembacaan database dibungkus try/catch
     * supaya halaman publik tetap tampil walau koneksi database bermasalah.
     */
    function wa_admin_number(): ?string
    {
        try {
            $fromDb = (new \App\Models\SettingModel())->getValue('wa_admin', '');
        } catch (\Throwable $e) {
            $fromDb = null;
        }

        $normalized = wa_normalize($fromDb);
        if ($normalized !== null) {
            return $normalized;
        }

        // config('Salon')->waAdmin sudah memuat override dari .env (salon.waAdmin).
        return wa_normalize(config('Salon')->waAdmin ?? null);
    }
}

if (! function_exists('wa_admin_link')) {
    /** Tautan wa.me ke admin, atau null kalau nomor admin belum valid. */
    function wa_admin_link(string $message = ''): ?string
    {
        return wa_link(wa_admin_number(), $message);
    }
}

if (! function_exists('wa_admin_display')) {
    /** Nomor admin siap tampil (0878-6218-3074), atau null kalau belum valid. */
    function wa_admin_display(): ?string
    {
        return wa_display(wa_admin_number());
    }
}
