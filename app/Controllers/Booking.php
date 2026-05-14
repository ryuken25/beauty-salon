<?php

namespace App\Controllers;

use App\Models\BookingLogModel;
use App\Models\BookingModel;
use App\Models\LayananModel;
use App\Models\SettingModel;
use App\Services\BookingService;
use App\Services\SlotService;
use App\Services\WhatsAppTemplateService;
use RuntimeException;

class Booking extends BaseController
{
    public function form()
    {
        $svc = new BookingService();
        if ($this->request->getMethod() === 'POST') {
            $rules = [
                'nama_pelanggan' => 'required|min_length[3]|max_length[100]',
                'nomor_hp_pelanggan' => 'required|regex_match[/^(\+?62|0)8[0-9]{7,12}$/]',
                'layanan_id' => 'required|is_natural_no_zero',
                'tanggal' => 'required|valid_date[Y-m-d]',
                'slot_mulai' => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]',
            ];
            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
            }
            try {
                $userId = session('user_role') === 'pelanggan' ? (int) session('user_id') : null;
                $row = $svc->create([
                    'nama_pelanggan' => $this->request->getPost('nama_pelanggan'),
                    'nomor_hp_pelanggan' => $this->request->getPost('nomor_hp_pelanggan'),
                    'layanan_id' => (int) $this->request->getPost('layanan_id'),
                    'tanggal' => $this->request->getPost('tanggal'),
                    'slot_mulai' => $this->request->getPost('slot_mulai'),
                    'catatan' => $this->request->getPost('catatan'),
                    'sumber' => 'online',
                    'actor' => 'pelanggan',
                    'actor_role' => 'pelanggan',
                    'user_id' => $userId,
                ]);
                return redirect()->to('/booking/sukses/' . $row['kode_booking']);
            } catch (RuntimeException $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }
        $layanans = (new LayananModel())->where('is_active', 1)->orderBy('kategori')->orderBy('nama')->find();
        $slot = new SlotService();
        $allSlots = $slot->allSlots();
        $rangeHari = $slot->rangeHari();
        $dates = [];
        for ($i = 0; $i <= $rangeHari; $i++) {
            $d = date('Y-m-d', strtotime("+{$i} days"));
            $dates[] = $d;
        }
        return view('public/booking_form', [
            'layanans' => $layanans,
            'all_slots' => $allSlots,
            'dates' => $dates,
            'preselect_layanan_id' => (int) ($this->request->getGet('layanan_id') ?? 0),
            'prefill_nama' => session('user_role') === 'pelanggan' ? (string) session('user_nama') : '',
            'prefill_hp' => session('user_role') === 'pelanggan' ? (string) session('user_hp') : '',
        ]);
    }

    public function sukses(string $kode)
    {
        $row = (new BookingModel())->detailByKode($kode);
        if (! $row) {
            return redirect()->to('/cek-booking')->with('error', 'Booking tidak ditemukan.');
        }
        $set = new SettingModel();
        $owner = $set->getValue('nomor_hp_owner', '');
        $template = (new WhatsAppTemplateService())->ownerConfirmationMessage($row);
        $waLink = $owner ? 'https://wa.me/' . preg_replace('/\D+/', '', $owner) . '?text=' . rawurlencode($template) : '';
        return view('public/booking_sukses', ['booking' => $row, 'wa_link' => $waLink]);
    }

    public function cek()
    {
        $bookings = [];
        $phone = null;
        if ($this->request->getMethod() === 'POST') {
            $phone = (new BookingService())->normalizePhone((string) $this->request->getPost('nomor_hp'));
            if ($phone !== '') {
                $bookings = (new BookingModel())->findByNomorHp($phone);
            }
        }
        return view('public/cek_booking', ['bookings' => $bookings, 'phone' => $phone]);
    }

    public function redirectCek()
    {
        return redirect()->to('/cek-booking');
    }

    public function detail(string $kode)
    {
        $row = (new BookingModel())->detailByKode($kode);
        if (! $row) {
            return redirect()->to('/cek-booking')->with('error', 'Booking tidak ditemukan.');
        }
        $phone = (new BookingService())->normalizePhone((string) $this->request->getGet('no_hp'));
        if ($phone === '' || $phone !== $row['nomor_hp_pelanggan']) {
            return redirect()->to('/cek-booking')->with('error', 'Kode booking dan nomor HP tidak cocok.');
        }
        $logs = (new BookingLogModel())->forBooking((int) $row['id']);
        $owner = (new SettingModel())->getValue('nomor_hp_owner', '');
        $waText = view('public/_template_wa_owner', ['booking' => $row], ['saveData' => true]);
        $waLink = $owner ? 'https://wa.me/' . preg_replace('/\D+/', '', $owner) . '?text=' . rawurlencode($waText) : '';
        return view('public/booking_detail', ['booking' => $row, 'logs' => $logs, 'wa_link' => $waLink, 'phone' => $phone]);
    }

    public function batal(string $kode)
    {
        $row = (new BookingModel())->detailByKode($kode);
        $phone = (new BookingService())->normalizePhone((string) $this->request->getPost('nomor_hp'));
        if (! $row || $phone === '' || $phone !== $row['nomor_hp_pelanggan']) {
            return redirect()->to('/cek-booking')->with('error', 'Booking tidak ditemukan atau nomor tidak cocok.');
        }
        try {
            (new BookingService())->cancel((int) $row['id'], 'pelanggan');
            return redirect()->to('/booking/' . $row['kode_booking'] . '?no_hp=' . urlencode($phone))->with('success', 'Booking berhasil dibatalkan.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
