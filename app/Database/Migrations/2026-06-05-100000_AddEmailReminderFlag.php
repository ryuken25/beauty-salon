<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Flag for the 30-minute pre-session email reminder (FASE 14C):
 *  - NULL  = belum dikirim → kandidat reminder
 *  - DATETIME = sudah dikirim (atau di-skip karena SMTP off) — tidak diulang
 *
 * Set bersamaan dengan ENGINE state setelah `sendBookingReminder()` selesai,
 * supaya satu booking maksimal dapat satu email pengingat.
 */
class AddEmailReminderFlag extends Migration
{
    public function up()
    {
        if (! $this->hasColumn('bookings', 'email_reminder_sent_at')) {
            $this->db->query('ALTER TABLE bookings ADD COLUMN email_reminder_sent_at DATETIME NULL AFTER payment_status');
        }
    }

    public function down()
    {
        if ($this->hasColumn('bookings', 'email_reminder_sent_at')) {
            $this->db->query('ALTER TABLE bookings DROP COLUMN email_reminder_sent_at');
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
