<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCancellationReason extends Migration
{
    public function up()
    {
        $fields = $this->db->getFieldNames('bookings');
        if (! in_array('cancellation_reason', $fields, true)) {
            $this->forge->addColumn('bookings', [
                'cancellation_reason' => [
                    'type' => 'TEXT', 'null' => true,
                    'after' => 'cancelled_by',
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('bookings', 'cancellation_reason');
    }
}
