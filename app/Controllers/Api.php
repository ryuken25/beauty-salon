<?php

namespace App\Controllers;

use App\Models\StylistModel;
use App\Services\SlotService;
use RuntimeException;

class Api extends BaseController
{
    public function slots()
    {
        $tanggal = (string) $this->request->getGet('tanggal');
        $layananId = (int) $this->request->getGet('layanan_id');
        $stylistId = (int) ($this->request->getGet('stylist_id') ?? 0);
        if (! $stylistId) {
            $stylist = (new StylistModel())->defaultActive();
            if (! $stylist) return $this->response->setJSON(['error' => 'Belum ada stylist.'])->setStatusCode(400);
            $stylistId = (int) $stylist['id'];
        }
        try {
            $av = (new SlotService())->availabilityFor($layananId, $stylistId, $tanggal);
        } catch (RuntimeException $e) {
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(400);
        }
        return $this->response->setJSON([
            'tanggal' => $tanggal,
            'layanan_id' => $layananId,
            'stylist_id' => $stylistId,
            'durasi_menit' => $av['durasi_menit'],
            'jumlah_slot' => $av['jumlah_slot'],
            'all_slots' => $av['all_slots'],
            'booked' => $av['booked'],
            'is_libur' => $av['is_libur'],
            'stylist_open' => $av['stylist_open'],
            'stylist_close' => $av['stylist_close'],
        ]);
    }
}
