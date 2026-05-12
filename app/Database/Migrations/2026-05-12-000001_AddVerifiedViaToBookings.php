<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVerifiedViaToBookings extends Migration
{
    public function up()
    {
        $this->forge->addColumn('bookings', [
            'verified_via' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'verified_by_telegram_chat_id'],
            'telegram_message_chat_id' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'verified_via'],
            'telegram_message_id' => ['type' => 'BIGINT', 'null' => true, 'after' => 'telegram_message_chat_id'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('bookings', ['verified_via', 'telegram_message_chat_id', 'telegram_message_id']);
    }
}
