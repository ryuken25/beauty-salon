<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPelangganRoleAndUserBookings extends Migration
{
    public function up()
    {
        $db = $this->db;

        $db->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin','pemilik','pelanggan') NOT NULL");

        $fields = $db->getFieldNames('users');
        if (! in_array('nomor_hp', $fields, true)) {
            $this->forge->addColumn('users', [
                'nomor_hp' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'nama'],
            ]);
        }

        $bookingFields = $db->getFieldNames('bookings');
        if (! in_array('user_id', $bookingFields, true)) {
            $this->forge->addColumn('bookings', [
                'user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'kode_booking'],
            ]);
            $db->query('ALTER TABLE bookings ADD CONSTRAINT bookings_user_id_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE');
            $db->query('CREATE INDEX bookings_user_id_idx ON bookings (user_id)');
        }
    }

    public function down()
    {
        $db = $this->db;
        try {
            $db->query('ALTER TABLE bookings DROP FOREIGN KEY bookings_user_id_fk');
        } catch (\Throwable $e) {}
        try {
            $db->query('DROP INDEX bookings_user_id_idx ON bookings');
        } catch (\Throwable $e) {}
        try {
            $this->forge->dropColumn('bookings', 'user_id');
        } catch (\Throwable $e) {}
        try {
            $this->forge->dropColumn('users', 'nomor_hp');
        } catch (\Throwable $e) {}
        try {
            $db->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin','pemilik') NOT NULL");
        } catch (\Throwable $e) {}
    }
}
