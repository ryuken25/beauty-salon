<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'INT', 'unsigned' => true],
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
    }

    public function down()
    {
        $this->forge->dropTable('booking_logs', true);
    }
}
