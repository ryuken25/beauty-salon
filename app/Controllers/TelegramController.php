<?php

namespace App\Controllers;

use App\Services\TelegramService;

class TelegramController extends BaseController
{
    public function webhook()
    {
        $secret = env('TELEGRAM_WEBHOOK_SECRET');
        if ($secret && $this->request->getHeaderLine('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }
        $payload = json_decode($this->request->getBody(), true) ?: [];
        (new TelegramService())->handleUpdate($payload);
        return $this->response->setJSON(['ok' => true]);
    }
}
