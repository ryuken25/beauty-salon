<?php

namespace App\Controllers;

use App\Models\LayananModel;
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

    /** Daftar layanan promo aktif untuk popup login (publik). */
    public function promos()
    {
        $items = (new LayananModel())->promoAktif(12);
        $out = [];
        foreach ($items as $l) {
            $out[] = [
                'id' => (int) $l['id'],
                'nama' => $l['nama'],
                'kategori' => $l['kategori'],
                'ikon' => $l['ikon'] ?? 'bi-stars',
                'harga' => (int) $l['harga'],
                'promo_persen' => (int) $l['promo_persen'],
                'promo_deskripsi' => $l['promo_deskripsi'] ?? null,
                'harga_final' => LayananModel::hargaFinal($l),
                'cover' => LayananModel::cover($l),
            ];
        }
        return $this->response->setJSON(['promos' => $out]);
    }

    /** Daftar layanan baru (14 hari terakhir) untuk popup login (publik). */
    public function baru()
    {
        $items = (new LayananModel())->baruAktif(12);
        $out = [];
        foreach ($items as $l) {
            $out[] = [
                'id'       => (int) $l['id'],
                'nama'     => $l['nama'],
                'kategori' => $l['kategori'],
                'ikon'     => $l['ikon'] ?? 'bi-stars',
                'harga'    => (int) $l['harga'],
                'cover'    => LayananModel::cover($l),
                'umur_hari' => LayananModel::umurHariBaru($l),
            ];
        }
        return $this->response->setJSON(['baru' => $out]);
    }
}
