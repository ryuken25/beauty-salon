<?php

namespace App\Commands;

use App\Models\BookingModel;
use App\Services\NotificationService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * One-off dev command to exercise the 3 email triggers (created / verified /
 * reminder) against a single recipient. Usage:
 *
 *     php spark email:test recipient@example.com
 *
 * Loads the first 3 booking rows in the seeded DB, overrides their
 * email_pelanggan in-memory only, and calls NotificationService directly.
 */
class EmailTest extends BaseCommand
{
    protected $group = 'email';
    protected $name = 'email:test';
    protected $description = 'Kirim 3 email contoh (created/verified/reminder) ke alamat yang diberikan.';
    protected $usage = 'email:test [recipient]';
    protected $arguments = ['recipient' => 'Email tujuan'];

    public function run(array $params)
    {
        $to = $params[0] ?? '';
        if ($to === '') {
            CLI::error('Recipient kosong. Contoh: php spark email:test you@gmail.com');
            return;
        }
        $svc = new NotificationService();
        if (! $svc->isEnabled()) {
            CLI::error('SMTP belum aktif — cek email.SMTPHost dan email.SMTPPass di .env.');
            return;
        }
        $model = new BookingModel();
        $rows = $model->orderBy('id')->findAll(3);
        if (count($rows) < 3) {
            CLI::error('Butuh minimal 3 booking di DB. Jalankan php spark db:seed SalonSeeder dulu.');
            return;
        }
        foreach ($rows as $i => $base) {
            $row = $model->detail((int) $base['id']);
            if (! $row) continue;
            $row['email_pelanggan'] = $to;
            $row['nama_pelanggan'] = $row['nama_pelanggan'] ?: 'Tester';
            switch ($i) {
                case 0:
                    $ok = $svc->sendBookingCreated($row);
                    CLI::write(($ok ? '[OK] ' : '[GAGAL] ') . "sendBookingCreated -> {$to} (booking {$row['kode_booking']})", $ok ? 'green' : 'red');
                    break;
                case 1:
                    $ok = $svc->sendBookingVerified($row);
                    CLI::write(($ok ? '[OK] ' : '[GAGAL] ') . "sendBookingVerified -> {$to} (booking {$row['kode_booking']})", $ok ? 'green' : 'red');
                    break;
                case 2:
                    $ok = $svc->sendBookingReminder($row);
                    CLI::write(($ok ? '[OK] ' : '[GAGAL] ') . "sendBookingReminder -> {$to} (booking {$row['kode_booking']})", $ok ? 'green' : 'red');
                    break;
            }
        }
        CLI::write('Selesai. Cek inbox + folder Spam.', 'yellow');
    }
}
