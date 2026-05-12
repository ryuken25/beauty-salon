<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ResetAndCreateSalonSchema extends Migration
{
    public function up()
    {
        $db = $this->db;
        $db->disableForeignKeyChecks();
        foreach ([
            'booking_logs', 'telegram_action_tokens', 'notification_logs',
            'transactions', 'transaksi', 'booking_slots', 'bookings',
            'stylist_day_offs', 'stylist_working_hours', 'stylist_schedules',
            'stylists', 'services', 'layanan',
            'customers', 'app_settings', 'settings', 'users',
        ] as $legacy) {
            $this->forge->dropTable($legacy, true);
        }
        $db->enableForeignKeyChecks();

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 100],
            'role' => ['type' => 'ENUM', 'constraint' => ['admin', 'pemilik']],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 100],
            'nomor_hp' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'peran' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('stylists', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'stylist_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'hari' => ['type' => 'ENUM', 'constraint' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']],
            'jam_mulai' => ['type' => 'TIME'],
            'jam_selesai' => ['type' => 'TIME'],
            'is_libur' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['stylist_id', 'hari']);
        $this->forge->addForeignKey('stylist_id', 'stylists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('stylist_schedules', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 100],
            'kategori' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'deskripsi' => ['type' => 'TEXT', 'null' => true],
            'durasi_menit' => ['type' => 'SMALLINT', 'unsigned' => true],
            'harga' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'ikon' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('layanan', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'kode_booking' => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama_pelanggan' => ['type' => 'VARCHAR', 'constraint' => 100],
            'nomor_hp_pelanggan' => ['type' => 'VARCHAR', 'constraint' => 20],
            'layanan_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'stylist_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'tanggal' => ['type' => 'DATE'],
            'slot_mulai' => ['type' => 'TIME'],
            'slot_selesai' => ['type' => 'TIME'],
            'jumlah_slot' => ['type' => 'SMALLINT', 'unsigned' => true],
            'harga_layanan' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending_verification', 'accepted', 'rejected', 'cancelled', 'completed'], 'default' => 'pending_verification'],
            'sumber' => ['type' => 'ENUM', 'constraint' => ['online', 'walkin'], 'default' => 'online'],
            'catatan' => ['type' => 'TEXT', 'null' => true],
            'wa_sent' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'verified_via' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'verified_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'cancelled_at' => ['type' => 'DATETIME', 'null' => true],
            'cancelled_by' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'rejection_reason' => ['type' => 'TEXT', 'null' => true],
            'telegram_message_chat_id' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'telegram_message_id' => ['type' => 'BIGINT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode_booking');
        $this->forge->addKey(['tanggal', 'slot_mulai']);
        $this->forge->addKey('status');
        $this->forge->addKey('nomor_hp_pelanggan');
        $this->forge->addForeignKey('layanan_id', 'layanan', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('stylist_id', 'stylists', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('bookings', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'stylist_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'tanggal' => ['type' => 'DATE'],
            'slot_waktu' => ['type' => 'TIME'],
            'status' => ['type' => 'ENUM', 'constraint' => ['held', 'released'], 'default' => 'held'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['stylist_id', 'tanggal', 'slot_waktu']);
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('stylist_id', 'stylists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('booking_slots', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'nominal' => ['type' => 'INT', 'unsigned' => true],
            'metode_bayar' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'cash'],
            'tanggal_transaksi' => ['type' => 'DATETIME'],
            'catatan' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('booking_id');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('transaksi', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'key_name' => ['type' => 'VARCHAR', 'constraint' => 60],
            'value' => ['type' => 'TEXT', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('key_name');
        $this->forge->createTable('settings', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'actor' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'actor_role' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'payload' => ['type' => 'JSON', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['booking_id', 'created_at']);
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('booking_logs', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'action' => ['type' => 'ENUM', 'constraint' => ['verify', 'reject']],
            'token' => ['type' => 'VARCHAR', 'constraint' => 80],
            'expires_at' => ['type' => 'DATETIME'],
            'used_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('telegram_action_tokens', true);
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        foreach (['telegram_action_tokens', 'booking_logs', 'settings', 'transaksi', 'booking_slots', 'bookings', 'layanan', 'stylist_schedules', 'stylists', 'users'] as $t) {
            $this->forge->dropTable($t, true);
        }
        $this->db->enableForeignKeyChecks();
    }
}
