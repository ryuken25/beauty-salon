<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropTelegramArtifacts extends Migration
{
    public function up()
    {
        $db = $this->db;

        // Drop telegram_action_tokens table
        $this->forge->dropTable('telegram_action_tokens', true);

        // Drop telegram_* columns from bookings (only if they exist)
        $bookingFields = $db->getFieldNames('bookings');
        if (in_array('telegram_message_chat_id', $bookingFields, true)) {
            $this->forge->dropColumn('bookings', 'telegram_message_chat_id');
        }
        if (in_array('telegram_message_id', $bookingFields, true)) {
            $this->forge->dropColumn('bookings', 'telegram_message_id');
        }

        // Remove leftover telegram settings rows
        if ($db->tableExists('settings')) {
            $db->table('settings')->whereIn('key_name', [
                'telegram_bot_token',
                'telegram_allowed_chat_ids',
                'telegram_super_admin_chat_id',
                'telegram_last_update_id',
            ])->delete();
        }

        // Remove telegram_sent rows from booking_logs
        if ($db->tableExists('booking_logs')) {
            $db->table('booking_logs')->where('event_type', 'telegram_sent')->delete();
        }
    }

    public function down()
    {
        // One-way removal; restoration handled by older baseline migration.
    }
}
