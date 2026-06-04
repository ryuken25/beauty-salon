<?php

namespace App\Commands;

use App\Services\BookingService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SendBookingReminders extends BaseCommand
{
    protected $group = 'bookings';
    protected $name = 'bookings:send-reminders';
    protected $description = 'Kirim email pengingat untuk booking accepted yang dimulai <=30 menit lagi.';

    public function run(array $params)
    {
        $count = (new BookingService())->sendDueReminders();
        if ($count === 0) {
            CLI::write('Tidak ada reminder yang perlu dikirim.', 'yellow');
        } else {
            CLI::write("Memproses {$count} reminder.", 'green');
        }
    }
}
