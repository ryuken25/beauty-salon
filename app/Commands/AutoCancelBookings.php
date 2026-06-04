<?php

namespace App\Commands;

use App\Services\BookingService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AutoCancelBookings extends BaseCommand
{
    protected $group = 'bookings';
    protected $name = 'bookings:auto-cancel';
    protected $description = 'Batalkan booking pending_verification yang tanggal+jam mulainya sudah lewat.';

    public function run(array $params)
    {
        $count = (new BookingService())->autoCancelExpired();
        if ($count === 0) {
            CLI::write('Tidak ada booking kedaluwarsa.', 'yellow');
        } else {
            CLI::write("Berhasil membatalkan {$count} booking kedaluwarsa.", 'green');
        }
    }
}
