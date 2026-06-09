<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Upgrade v2: galeri foto + promo per layanan, TANPA tabel baru.
 *
 *  - gambar          JSON NULL — array path relatif (FCPATH/<path>), [0] = cover.
 *                    Di MariaDB lama, JSON adalah alias LONGTEXT — tetap jalan.
 *  - promo_persen    TINYINT UNSIGNED NULL — 0..100. NULL/0 = tanpa promo.
 *  - promo_deskripsi VARCHAR(255) NULL — teks marketing opsional.
 *
 * Idempoten via probe information_schema (jangan getFieldNames() — CI4 nge-cache).
 */
class AddLayananPromoIconGambar extends Migration
{
    public function up()
    {
        $db = $this->db;
        if (! $this->hasColumn('layanan', 'gambar')) {
            $db->query('ALTER TABLE layanan ADD COLUMN gambar JSON NULL DEFAULT NULL AFTER ikon');
        }
        if (! $this->hasColumn('layanan', 'promo_persen')) {
            $db->query('ALTER TABLE layanan ADD COLUMN promo_persen TINYINT UNSIGNED NULL DEFAULT NULL AFTER gambar');
        }
        if (! $this->hasColumn('layanan', 'promo_deskripsi')) {
            $db->query('ALTER TABLE layanan ADD COLUMN promo_deskripsi VARCHAR(255) NULL DEFAULT NULL AFTER promo_persen');
        }
    }

    public function down()
    {
        $db = $this->db;
        foreach (['promo_deskripsi', 'promo_persen', 'gambar'] as $col) {
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
