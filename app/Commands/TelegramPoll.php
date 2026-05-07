<?php

namespace App\Commands;

use App\Services\TelegramService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TelegramPoll extends BaseCommand
{
    protected $group = 'Telegram';
    protected $name = 'telegram:poll';
    protected $description = 'Menjalankan Telegram Bot API long polling untuk mode lokal tanpa HTTPS.';

    public function run(array $params)
    {
        CLI::write('Telegram long polling berjalan. Tekan Ctrl+C untuk berhenti.', 'green');
        $service = new TelegramService();
        while (true) {
            $service->pollOnce();
            sleep(1);
        }
    }
}
