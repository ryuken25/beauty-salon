<?php

namespace App\Controllers;

use App\Services\TelegramService;

class TelegramController extends BaseController
{
    public function webhook()
    {
        $update = json_decode($this->request->getBody() ?: '', true);
        if (! is_array($update)) {
            return $this->response->setStatusCode(400)->setBody('Invalid payload');
        }
        try {
            (new TelegramService())->handleUpdate($update);
        } catch (\Throwable $e) {
            log_message('error', 'Telegram webhook gagal: ' . $e->getMessage());
        }
        return $this->response->setBody('OK');
    }
}
