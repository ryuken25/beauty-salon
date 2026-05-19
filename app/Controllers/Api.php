<?php

namespace App\Controllers;

use App\Services\SlotService;
use RuntimeException;

class Api extends BaseController
{
    public function slots()
    {
        $tanggal = (string) $this->request->getGet('tanggal');
        $layananId = (int) $this->request->getGet('layanan_id');
        try {
            $av = (new SlotService())->availabilityFor($layananId, $tanggal);
        } catch (RuntimeException $e) {
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(400);
        }
        return $this->response->setJSON([
            'tanggal' => $tanggal,
            'layanan_id' => $layananId,
            'durasi_menit' => $av['durasi_menit'],
            'jumlah_slot' => $av['jumlah_slot'],
            'all_slots' => $av['all_slots'],
            'booked' => $av['booked'],
        ]);
    }
}
