<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Promo rentang tanggal: layanan.promo_mulai / promo_selesai (DATE, nullable).
 * Promo aktif kalau:
 *   - promo_persen > 0
 *   - DAN (promo_mulai IS NULL atau today >= promo_mulai)
 *   - DAN (promo_selesai IS NULL atau today <= promo_selesai)
 *
 * NULL di kedua sisi = promo berlaku tanpa batas (untuk yang memang tetap
 * promo). Probe via information_schema (jangan getFieldNames).
 */
class AddPromoDateRange extends Migration
{
    public function up()
    {
        $db = $this->db;
        if (! $this->hasColumn('layanan', 'promo_mulai')) {
            $db->query('ALTER TABLE layanan ADD COLUMN promo_mulai DATE NULL DEFAULT NULL AFTER promo_persen');
        }
        if (! $this->hasColumn('layanan', 'promo_selesai')) {
            $db->query('ALTER TABLE layanan ADD COLUMN promo_selesai DATE NULL DEFAULT NULL AFTER promo_mulai');
        }
    }

    public function down()
    {
        $db = $this->db;
        foreach (['promo_selesai', 'promo_mulai'] as $col) {
            if ($this->hasColumn('layanan', $col)) {
                $db->query("ALTER TABLE layanan DROP COLUMN {$col}");
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
