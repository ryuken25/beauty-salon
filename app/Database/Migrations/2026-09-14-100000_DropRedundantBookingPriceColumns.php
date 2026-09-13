<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rapikan tabel `bookings` (revisi sidang, 14 September 2026).
 *
 * Migration 2026-06-19 menambahkan blok snapshot harga. Sebagian kolomnya
 * ternyata tidak membawa informasi baru dan dibuang di sini:
 *
 *  - final_service_price  → selalu diisi nilai yang sama persis dengan
 *                           harga_layanan (BookingService menulis variabel
 *                           $hargaFinal ke dua kolom sekaligus).
 *  - promo_id             → selalu diisi id layanan, padahal kolom layanan_id
 *                           di tabel yang sama sudah menyimpannya.
 *  - promo_discount_type  → nilainya hanya 'percentage' atau NULL. Sistem ini
 *                           tidak punya jenis potongan lain, jadi kolomnya
 *                           tidak membedakan apa pun.
 *
 * Kolom snapshot yang TETAP dipertahankan karena memang membawa informasi
 * historis yang tidak bisa direkonstruksi dari tabel lain:
 *  - original_service_price (harga normal saat booking dibuat)
 *  - promo_name, promo_discount_value (promo yang berlaku saat itu)
 *  - remaining_payment (sisa tagihan setelah DP)
 *
 * Probe kolom lewat information_schema, bukan getFieldNames() — CI4 menyimpan
 * hasil getFieldNames() untuk satu putaran migrate penuh.
 */
class DropRedundantBookingPriceColumns extends Migration
{
    private const DROPPED = ['final_service_price', 'promo_id', 'promo_discount_type'];

    public function up()
    {
        foreach (self::DROPPED as $col) {
            if ($this->hasColumn('bookings', $col)) {
                $this->db->query("ALTER TABLE bookings DROP COLUMN {$col}");
            }
        }
    }

    public function down()
    {
        $db = $this->db;

        if (! $this->hasColumn('bookings', 'promo_id')) {
            $db->query('ALTER TABLE bookings ADD COLUMN promo_id INT UNSIGNED NULL DEFAULT NULL AFTER original_service_price');
        }
        if (! $this->hasColumn('bookings', 'promo_discount_type')) {
            $db->query('ALTER TABLE bookings ADD COLUMN promo_discount_type VARCHAR(50) NULL DEFAULT NULL AFTER promo_name');
        }
        if (! $this->hasColumn('bookings', 'final_service_price')) {
            $db->query('ALTER TABLE bookings ADD COLUMN final_service_price INT UNSIGNED NOT NULL DEFAULT 0 AFTER promo_discount_value');
        }

        // Isi ulang dari sumber yang masih ada supaya rollback tidak
        // meninggalkan kolom kosong.
        $db->query('UPDATE bookings SET final_service_price = harga_layanan');
        $db->query("UPDATE bookings SET promo_id = layanan_id, promo_discount_type = 'percentage' WHERE promo_discount_value > 0");
    }

    private function hasColumn(string $table, string $column): bool
    {
        $rows = $this->db->query(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$this->db->getDatabase(), $table, $column]
        )->getResultArray();

        return $rows !== [];
    }
}
