<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBookingPriceSnapshotAndDpVerifiedAt extends Migration
{
    public function up()
    {
        $db = $this->db;

        // Cek kolom menggunakan information_schema untuk menghindari cache metadata CI4
        if (! $this->hasColumn('bookings', 'original_service_price')) {
            $db->query('ALTER TABLE bookings ADD COLUMN original_service_price INT UNSIGNED NOT NULL DEFAULT 0 AFTER harga_layanan');
        }
        if (! $this->hasColumn('bookings', 'promo_id')) {
            $db->query('ALTER TABLE bookings ADD COLUMN promo_id INT UNSIGNED NULL DEFAULT NULL AFTER original_service_price');
        }
        if (! $this->hasColumn('bookings', 'promo_name')) {
            $db->query('ALTER TABLE bookings ADD COLUMN promo_name VARCHAR(150) NULL DEFAULT NULL AFTER promo_id');
        }
        if (! $this->hasColumn('bookings', 'promo_discount_type')) {
            $db->query('ALTER TABLE bookings ADD COLUMN promo_discount_type VARCHAR(50) NULL DEFAULT NULL AFTER promo_name');
        }
        if (! $this->hasColumn('bookings', 'promo_discount_value')) {
            $db->query('ALTER TABLE bookings ADD COLUMN promo_discount_value INT UNSIGNED NOT NULL DEFAULT 0 AFTER promo_discount_type');
        }
        if (! $this->hasColumn('bookings', 'final_service_price')) {
            $db->query('ALTER TABLE bookings ADD COLUMN final_service_price INT UNSIGNED NOT NULL DEFAULT 0 AFTER promo_discount_value');
        }
        if (! $this->hasColumn('bookings', 'remaining_payment')) {
            $db->query('ALTER TABLE bookings ADD COLUMN remaining_payment INT UNSIGNED NOT NULL DEFAULT 0 AFTER final_service_price');
        }
        if (! $this->hasColumn('bookings', 'dp_verified_at')) {
            $db->query('ALTER TABLE bookings ADD COLUMN dp_verified_at DATETIME NULL DEFAULT NULL AFTER payment_status');
        }

        // Backfill data lama: original_service_price = harga_layanan, final_service_price = harga_layanan, remaining_payment = harga_layanan - dp_amount
        $db->query('UPDATE bookings SET original_service_price = harga_layanan, final_service_price = harga_layanan, remaining_payment = GREATEST(0, CAST(harga_layanan AS SIGNED) - CAST(dp_amount AS SIGNED))');
        
        // Backfill dp_verified_at = verified_at untuk booking yang status pembayarannya dp_verified
        $db->query("UPDATE bookings SET dp_verified_at = verified_at WHERE payment_status = 'dp_verified' AND verified_at IS NOT NULL");
    }

    public function down()
    {
        $db = $this->db;
        $cols = [
            'original_service_price', 'promo_id', 'promo_name', 
            'promo_discount_type', 'promo_discount_value', 
            'final_service_price', 'remaining_payment', 'dp_verified_at'
        ];
        foreach ($cols as $col) {
            if ($this->hasColumn('bookings', $col)) {
                $db->query("ALTER TABLE bookings DROP COLUMN {$col}");
            }
        }
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
