<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Business-decision sync (2026-06-04):
 *  - Customers MUST have accounts again (re-introduce pelanggan role,
 *    unique nomor_hp, bookings.user_id linkage).
 *  - DP (down payment) tracking: dp_amount + dp_proof_path + payment_status
 *    columns on bookings.
 *  - Stylist removed end-to-end (FK + column on bookings, then drop stylists
 *    table). All stylist code is being purged in the same batch.
 *
 * Column existence is probed via information_schema (not getFieldNames(),
 * which CI4 caches for the whole migrate run).
 */
class AuthPelangganDpRemoveStylist extends Migration
{
    public function up()
    {
        $db = $this->db;

        // ── 1. Re-enable 'pelanggan' role on users ────────────────
        $db->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin','pemilik','pelanggan') NOT NULL");

        // ── 2. Unique index on users.nomor_hp (MySQL allows many NULLs) ─
        if (! $this->hasIndex('users', 'users_nomor_hp_unique')) {
            $db->query('ALTER TABLE users ADD UNIQUE INDEX users_nomor_hp_unique (nomor_hp)');
        }

        // ── 3. bookings.user_id (FK -> users; nullable for walk-in) ─
        if (! $this->hasColumn('bookings', 'user_id')) {
            $db->query('ALTER TABLE bookings ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER kode_booking');
            $db->query('ALTER TABLE bookings ADD CONSTRAINT bookings_user_id_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE');
        }

        // ── 4. DP columns on bookings ─────────────────────────────
        if (! $this->hasColumn('bookings', 'dp_amount')) {
            $db->query('ALTER TABLE bookings ADD COLUMN dp_amount INT UNSIGNED NOT NULL DEFAULT 0 AFTER harga_layanan');
        }
        if (! $this->hasColumn('bookings', 'dp_proof_path')) {
            $db->query('ALTER TABLE bookings ADD COLUMN dp_proof_path VARCHAR(255) NULL AFTER dp_amount');
        }
        if (! $this->hasColumn('bookings', 'payment_status')) {
            $db->query("ALTER TABLE bookings ADD COLUMN payment_status ENUM('unpaid','dp_uploaded','dp_verified') NOT NULL DEFAULT 'unpaid' AFTER dp_proof_path");
        }

        // ── 5. Drop stylist linkage + table ───────────────────────
        if ($this->hasColumn('bookings', 'stylist_id')) {
            foreach ($this->foreignKeys('bookings', 'stylist_id') as $fk) {
                $db->query("ALTER TABLE bookings DROP FOREIGN KEY `{$fk}`");
            }
            $db->query('ALTER TABLE bookings DROP COLUMN stylist_id');
        }
        $this->forge->dropTable('stylists', true);
    }

    public function down()
    {
        $db = $this->db;

        // Reverse customer-side additions (stylist drop is one-way — see
        // 2026-05-17 baseline if a full restore is ever needed).
        foreach ($this->foreignKeys('bookings', 'user_id') as $fk) {
            $db->query("ALTER TABLE bookings DROP FOREIGN KEY `{$fk}`");
        }
        if ($this->hasColumn('bookings', 'user_id')) {
            $db->query('ALTER TABLE bookings DROP COLUMN user_id');
        }
        foreach (['payment_status', 'dp_proof_path', 'dp_amount'] as $col) {
            if ($this->hasColumn('bookings', $col)) {
                $db->query("ALTER TABLE bookings DROP COLUMN {$col}");
            }
        }
        if ($this->hasIndex('users', 'users_nomor_hp_unique')) {
            $db->query('ALTER TABLE users DROP INDEX users_nomor_hp_unique');
        }
        $db->table('users')->where('role', 'pelanggan')->delete();
        $db->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin','pemilik') NOT NULL");
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

    private function hasIndex(string $table, string $indexName): bool
    {
        $rows = $this->db->query(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$this->db->getDatabase(), $table, $indexName]
        )->getResultArray();
        return $rows !== [];
    }

    private function foreignKeys(string $table, string $column): array
    {
        $rows = $this->db->query(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$this->db->getDatabase(), $table, $column]
        )->getResultArray();
        return array_column($rows, 'CONSTRAINT_NAME');
    }
}
