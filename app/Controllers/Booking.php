<?php

namespace App\Controllers;

use App\Models\BookingLogModel;
use App\Models\BookingModel;
use App\Models\LayananModel;
use App\Models\SettingModel;
use App\Models\StylistModel;
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
            // Anti-spam #1 — honeypot. Bots fill every field; humans never see
            // the "website" input (hidden via CSS). A filled value = bot.
            if (trim((string) $this->request->getPost('website')) !== '') {
                return redirect()->to('/')->with('success', 'Terima kasih.');
            }
            // Anti-spam #2 — soft per-IP daily cap (no CAPTCHA, gaptek-friendly).
            $ipKey = 'booking_count_' . md5($this->request->getIPAddress());
            $todayCount = (int) (cache($ipKey) ?? 0);
            if ($todayCount >= 12) {
                return redirect()->back()->withInput()->with('error', 'Terlalu banyak booking dari perangkat ini hari ini. Silakan hubungi salon via WhatsApp.');
            }

            $rules = [
                'nama_pelanggan' => 'required|min_length[3]|max_length[100]',
                'nomor_hp_pelanggan' => 'required|regex_match[/^(\+?62|0)8[0-9]{7,12}$/]',
                'email_pelanggan' => 'permit_empty|valid_email|max_length[150]',
                'layanan_id' => 'required|is_natural_no_zero',
                'stylist_id' => 'required|is_natural_no_zero',
                'tanggal' => 'required|valid_date[Y-m-d]',
                'slot_mulai' => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]',
            ];
            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
            }
            try {
                $row = $svc->create([
                    'nama_pelanggan' => $this->request->getPost('nama_pelanggan'),
                    'nomor_hp_pelanggan' => $this->request->getPost('nomor_hp_pelanggan'),
                    'email_pelanggan' => $this->request->getPost('email_pelanggan'),
                    'layanan_id' => (int) $this->request->getPost('layanan_id'),
                    'stylist_id' => (int) $this->request->getPost('stylist_id'),
                    'tanggal' => $this->request->getPost('tanggal'),
                    'slot_mulai' => $this->request->getPost('slot_mulai'),
                    'catatan' => $this->request->getPost('catatan'),
                    'sumber' => 'online',
                    'actor' => 'pelanggan',
                    'actor_role' => 'pelanggan',
                ]);
                cache()->save($ipKey, $todayCount + 1, DAY);
                return redirect()->to('/booking/sukses/' . $row['kode_booking']);
            } catch (RuntimeException $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }
        $slot = new SlotService();
        $rangeHari = $slot->rangeHari();
        $dates = [];
        for ($i = 0; $i <= $rangeHari; $i++) {
            $dates[] = date('Y-m-d', strtotime("+{$i} days"));
        }
        return view('public/booking_form', [
            'layanans' => (new LayananModel())->where('is_active', 1)->orderBy('kategori')->orderBy('nama')->find(),
            'stylists' => (new StylistModel())->activeForBooking(),
            'all_slots' => $slot->allSlots(),
            'dates' => $dates,
            'preselect_layanan_id' => (int) ($this->request->getGet('layanan_id') ?? 0),
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
        $waText = (new WhatsAppTemplateService())->ownerConfirmationMessage($row);
        $waLink = (new WhatsAppTemplateService())->ownerLink($waText);
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
            $reason = trim((string) $this->request->getPost('cancellation_reason'));
            (new BookingService())->cancel((int) $row['id'], 'pelanggan', null, $reason !== '' ? $reason : null);
            return redirect()->to('/booking/' . $row['kode_booking'] . '?no_hp=' . urlencode($phone))->with('success', 'Booking berhasil dibatalkan.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
