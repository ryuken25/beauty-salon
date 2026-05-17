<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTransaksiPriceBreakdown extends Migration
{
    public function up()
    {
        $db = $this->db;
        $fields = $db->getFieldNames('transaksi');

        if (! in_array('base_price', $fields, true)) {
            $this->forge->addColumn('transaksi', [
                'base_price' => [
                    'type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0,
                    'after' => 'nominal',
                ],
            ]);
        }
        if (! in_array('additional_price', $fields, true)) {
            $this->forge->addColumn('transaksi', [
                'additional_price' => [
                    'type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0,
                    'after' => 'base_price',
                ],
            ]);
        }

        // Backfill: legacy rows where the breakdown is unknown — treat the whole
        // nominal as base_price so historical numbers reconcile.
        $db->query('UPDATE transaksi SET base_price = nominal WHERE base_price = 0 AND nominal > 0');
    }

    public function down()
    {
        $this->forge->dropColumn('transaksi', 'base_price');
        $this->forge->dropColumn('transaksi', 'additional_price');
    }
}
