<?php

namespace App\Commands;

use App\Services\TelegramService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TelegramPoll extends BaseCommand
{
    protected $group = 'Salon';
    protected $name = 'telegram:poll';
    protected $description = 'Long-poll Telegram bot for updates (development).';

    public function run(array $params)
    {
        $svc = new TelegramService();
        if (! $svc->isConfigured()) {
            CLI::error('Telegram belum dikonfigurasi (token/chat_ids kosong di settings).');
            return;
        }
        CLI::write('Telegram polling dimulai. Ctrl+C untuk berhenti.', 'green');
        while (true) {
            try {
                $n = $svc->pollOnce();
                if ($n > 0) CLI::write("Diproses: {$n} update");
            } catch (\Throwable $e) {
                CLI::error('Error: ' . $e->getMessage());
                sleep(5);
            }
        }
    }
}
