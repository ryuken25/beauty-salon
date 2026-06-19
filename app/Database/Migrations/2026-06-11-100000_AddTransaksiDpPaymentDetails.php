<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTransaksiDpPaymentDetails extends Migration
{
    public function up()
    {
        $db = $this->db;
        
        // Cek kolom menggunakan information_schema untuk menghindari cache metadata CI4
        if (! $this->hasColumn('transaksi', 'dp_paid')) {
            $db->query('ALTER TABLE transaksi ADD COLUMN dp_paid INT UNSIGNED NOT NULL DEFAULT 0 AFTER additional_price');
        }
        if (! $this->hasColumn('transaksi', 'sisa_bayar')) {
            $db->query('ALTER TABLE transaksi ADD COLUMN sisa_bayar INT UNSIGNED NOT NULL DEFAULT 0 AFTER dp_paid');
        }

        // Backfill transaksi lama: sisa_bayar = nominal - dp_paid
        $db->query('UPDATE transaksi SET sisa_bayar = nominal WHERE sisa_bayar = 0');
    }

    public function down()
    {
        $db = $this->db;
        if ($this->hasColumn('transaksi', 'sisa_bayar')) {
            $db->query('ALTER TABLE transaksi DROP COLUMN sisa_bayar');
        }
        if ($this->hasColumn('transaksi', 'dp_paid')) {
            $db->query('ALTER TABLE transaksi DROP COLUMN dp_paid');
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
