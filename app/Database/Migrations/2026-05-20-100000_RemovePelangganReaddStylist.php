<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Business-decision sync (2026-05-20):
 *  - Customer login removed entirely (salon clientele are not tech-savvy).
 *    Drop the 'pelanggan' role and the bookings.user_id linkage.
 *  - Stylist feature re-introduced (full CRUD), so recreate the stylists
 *    table and re-attach bookings.stylist_id. Slots remain salon-wide —
 *    stylist is a labelled attribute on the booking, not a slot dimension.
 *  - Soft-delete column added to layanan + stylists.
 *  - bookings.email_pelanggan added (optional contact field).
 *
 * NOTE: column existence is probed via information_schema, NOT
 * $db->getFieldNames(). The latter caches the first result for the whole
 * migrate run, so an earlier migration that touched `bookings` would feed
 * this migration a stale column list.
 */
class RemovePelangganReaddStylist extends Migration
{
    public function up()
    {
        $db = $this->db;

        // ── Drop pelanggan login footprint ────────────────────────
        $db->table('users')->where('role', 'pelanggan')->delete();
        $db->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin','pemilik') NOT NULL");

        if ($this->hasColumn('bookings', 'user_id')) {
            foreach ($this->foreignKeys('bookings', 'user_id') as $fk) {
                $db->query("ALTER TABLE bookings DROP FOREIGN KEY `{$fk}`");
            }
            $db->query('ALTER TABLE bookings DROP COLUMN user_id');
        }

        // ── Optional customer email on the booking ────────────────
        if (! $this->hasColumn('bookings', 'email_pelanggan')) {
            $db->query("ALTER TABLE bookings ADD COLUMN email_pelanggan VARCHAR(150) NULL AFTER nomor_hp_pelanggan");
        }

        // ── Recreate stylists ─────────────────────────────────────
        if (! $db->tableExists('stylists')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'nama' => ['type' => 'VARCHAR', 'constraint' => 100],
                'foto' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'spesialisasi' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'jam_kerja_mulai' => ['type' => 'TIME', 'null' => true],
                'jam_kerja_selesai' => ['type' => 'TIME', 'null' => true],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
                'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('stylists', true);
        }

        // ── Re-attach bookings.stylist_id (nullable; old rows = NULL) ─
        if (! $this->hasColumn('bookings', 'stylist_id')) {
            $db->query('ALTER TABLE bookings ADD COLUMN stylist_id BIGINT UNSIGNED NULL AFTER layanan_id');
            $db->query('ALTER TABLE bookings ADD CONSTRAINT bookings_stylist_id_fk FOREIGN KEY (stylist_id) REFERENCES stylists(id) ON DELETE SET NULL ON UPDATE CASCADE');
        }

        // ── Soft-delete column for layanan ────────────────────────
        if (! $this->hasColumn('layanan', 'deleted_at')) {
            $db->query('ALTER TABLE layanan ADD COLUMN deleted_at DATETIME NULL');
        }
    }

    public function down()
    {
        $db = $this->db;
        foreach ($this->foreignKeys('bookings', 'stylist_id') as $fk) {
            $db->query("ALTER TABLE bookings DROP FOREIGN KEY `{$fk}`");
        }
        if ($this->hasColumn('bookings', 'stylist_id')) {
            $db->query('ALTER TABLE bookings DROP COLUMN stylist_id');
        }
        if ($this->hasColumn('bookings', 'email_pelanggan')) {
            $db->query('ALTER TABLE bookings DROP COLUMN email_pelanggan');
        }
        if ($this->hasColumn('layanan', 'deleted_at')) {
            $db->query('ALTER TABLE layanan DROP COLUMN deleted_at');
        }
        $this->forge->dropTable('stylists', true);
        $db->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin','pemilik','pelanggan') NOT NULL");
    }

    /** Live column-existence check (bypasses getFieldNames() caching). */
    private function hasColumn(string $table, string $column): bool
    {
        $rows = $this->db->query(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$this->db->getDatabase(), $table, $column]
        )->getResultArray();
        return $rows !== [];
    }

    /** FK constraint names on a given (table, column). */
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
