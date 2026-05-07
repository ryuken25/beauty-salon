<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSalonTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'email' => ['type' => 'VARCHAR', 'constraint' => 160],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'role' => ['type' => 'ENUM', 'constraint' => ['customer', 'admin', 'owner'], 'default' => 'customer'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30],
            'address' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('customers', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'category' => ['type' => 'VARCHAR', 'constraint' => 100],
            'name' => ['type' => 'VARCHAR', 'constraint' => 160],
            'description' => ['type' => 'TEXT', 'null' => true],
            'duration_minutes' => ['type' => 'INT', 'unsigned' => true],
            'price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('services', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'is_owner' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('stylists', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'stylist_id' => ['type' => 'INT', 'unsigned' => true],
            'day_of_week' => ['type' => 'TINYINT', 'unsigned' => true],
            'start_time' => ['type' => 'TIME'],
            'end_time' => ['type' => 'TIME'],
            'is_working' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('stylist_id', 'stylists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['stylist_id', 'day_of_week']);
        $this->forge->createTable('stylist_working_hours', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'stylist_id' => ['type' => 'INT', 'unsigned' => true],
            'date' => ['type' => 'DATE'],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('stylist_id', 'stylists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('stylist_day_offs', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'booking_code' => ['type' => 'VARCHAR', 'constraint' => 30],
            'customer_id' => ['type' => 'INT', 'unsigned' => true],
            'service_id' => ['type' => 'INT', 'unsigned' => true],
            'stylist_id' => ['type' => 'INT', 'unsigned' => true],
            'booking_date' => ['type' => 'DATE'],
            'start_time' => ['type' => 'TIME'],
            'end_time' => ['type' => 'TIME'],
            'slot_count' => ['type' => 'INT', 'unsigned' => true],
            'service_price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'source' => ['type' => 'ENUM', 'constraint' => ['online', 'walkin'], 'default' => 'online'],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending_verification', 'accepted', 'rejected', 'cancelled', 'completed'], 'default' => 'pending_verification'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'rejection_reason' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'verified_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'verified_by_telegram_chat_id' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'verified_at' => ['type' => 'DATETIME', 'null' => true],
            'cancelled_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'cancelled_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'wa_notified_at' => ['type' => 'DATETIME', 'null' => true],
            'wa_notified_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('booking_code');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('stylist_id', 'stylists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bookings', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'INT', 'unsigned' => true],
            'stylist_id' => ['type' => 'INT', 'unsigned' => true],
            'slot_date' => ['type' => 'DATE'],
            'slot_start' => ['type' => 'TIME'],
            'slot_end' => ['type' => 'TIME'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['stylist_id', 'slot_date', 'slot_start'], 'uq_booking_slots_stylist_date_start');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('stylist_id', 'stylists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('booking_slots', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'INT', 'unsigned' => true],
            'transaction_code' => ['type' => 'VARCHAR', 'constraint' => 30],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'payment_method' => ['type' => 'ENUM', 'constraint' => ['cash', 'transfer', 'other'], 'default' => 'cash'],
            'payment_note' => ['type' => 'TEXT', 'null' => true],
            'transaction_date' => ['type' => 'DATETIME'],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('booking_id');
        $this->forge->addUniqueKey('transaction_code');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('transactions', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'channel' => ['type' => 'ENUM', 'constraint' => ['telegram', 'whatsapp_manual'], 'default' => 'telegram'],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 80],
            'recipient' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'message' => ['type' => 'TEXT'],
            'status' => ['type' => 'ENUM', 'constraint' => ['success', 'failed', 'pending', 'manual_opened', 'manual_marked_sent'], 'default' => 'pending'],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('notification_logs', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'INT', 'unsigned' => true],
            'action' => ['type' => 'ENUM', 'constraint' => ['accept', 'reject']],
            'token' => ['type' => 'VARCHAR', 'constraint' => 80],
            'expires_at' => ['type' => 'DATETIME'],
            'used_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('telegram_action_tokens', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'setting_key' => ['type' => 'VARCHAR', 'constraint' => 120],
            'setting_value' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('setting_key');
        $this->forge->createTable('app_settings', true);
    }

    public function down()
    {
        foreach (['app_settings', 'telegram_action_tokens', 'notification_logs', 'transactions', 'booking_slots', 'bookings', 'stylist_day_offs', 'stylist_working_hours', 'stylists', 'services', 'customers', 'users'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
