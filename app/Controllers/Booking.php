<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\LayananModel;
use App\Models\SettingModel;
use App\Services\BookingService;
use App\Services\SlotService;
use RuntimeException;

class Booking extends BaseController
{
    public function form()
    {
        $svc = new BookingService();
        $slot = new SlotService();
        $settings = new SettingModel();

        if ($this->request->getMethod() === 'POST') {
            // Honeypot (bot trap).
            if (trim((string) $this->request->getPost('website')) !== '') {
                return redirect()->to('/')->with('success', 'Terima kasih.');
            }
            $ipKey = 'booking_count_' . md5($this->request->getIPAddress());
            $todayCount = (int) (cache($ipKey) ?? 0);
            if ($todayCount >= 12) {
                return redirect()->back()->withInput()->with('error', 'Terlalu banyak booking dari perangkat ini hari ini. Silakan hubungi salon via WhatsApp.');
            }

            $rules = [
                'layanan_id' => 'required|is_natural_no_zero',
                'tanggal' => 'required|valid_date[Y-m-d]',
                'slot_mulai' => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]',
                'bukti_dp' => 'uploaded[bukti_dp]|is_image[bukti_dp]|max_size[bukti_dp,2048]|mime_in[bukti_dp,image/png,image/jpg,image/jpeg,image/webp]',
            ];
            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
            }

            // Move bukti DP into public/uploads/dp/.
            $file = $this->request->getFile('bukti_dp');
            $uploadDir = FCPATH . 'uploads/dp';
            if (! is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $name = $file->getRandomName();
            $file->move($uploadDir, $name);
            $proofRelative = 'uploads/dp/' . $name;

            try {
                $row = $svc->create([
                    'user_id' => (int) session('user_id'),
                    'nama_pelanggan' => session('user_nama'),
                    'nomor_hp_pelanggan' => session('user_hp'),
                    'email_pelanggan' => session('user_email'),
                    'layanan_id' => (int) $this->request->getPost('layanan_id'),
                    'tanggal' => $this->request->getPost('tanggal'),
                    'slot_mulai' => $this->request->getPost('slot_mulai'),
                    'catatan' => $this->request->getPost('catatan'),
                    'dp_proof_path' => $proofRelative,
                    'sumber' => 'online',
                    'actor' => 'pelanggan:' . session('user_id'),
                    'actor_role' => 'pelanggan',
                ]);
                cache()->save($ipKey, $todayCount + 1, DAY);
                return redirect()->to('/booking/sukses/' . $row['kode_booking']);
            } catch (RuntimeException $e) {
                // Roll the orphan upload back so we don't leak files on validation errors.
                @unlink($uploadDir . '/' . $name);
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        $rangeHari = $slot->rangeHari();
        // Awareness: salon hampir tutup hari ini → start dari besok.
        $jamTutup = $slot->salonHours()['jam_tutup'];
        $nowMin = (int) date('H') * 60 + (int) date('i');
        [$hT, $mT] = array_map('intval', explode(':', $jamTutup));
        $closeMin = $hT * 60 + $mT;
        // Tutup kalau jam sekarang ≥ jam tutup, atau sisa waktu < 30 menit (1 slot).
        $closedToday = ($nowMin + SlotService::SLOT_MINUTES) > $closeMin;
        $offset = $closedToday ? 1 : 0;
        $dates = [];
        for ($i = $offset; $i <= $rangeHari; $i++) {
            $dates[] = date('Y-m-d', strtotime("+{$i} days"));
        }
        return view('public/booking_form', [
            'layanans' => (new LayananModel())->where('is_active', 1)->orderBy('kategori')->orderBy('nama')->find(),
            'all_slots' => $slot->allSlots(),
            'dates' => $dates,
            'preselect_layanan_id' => (int) ($this->request->getGet('layanan_id') ?? 0),
            'info_pembayaran_dp' => $settings->getValue('info_pembayaran_dp', ''),
            'qris_image_path' => $settings->getValue('qris_image_path', ''),
            'akun_nama' => session('user_nama'),
            'akun_hp' => session('user_hp'),
            'closed_today' => $closedToday,
            'jam_tutup' => $jamTutup,
        ]);
    }

    public function sukses(string $kode)
    {
        $row = (new BookingModel())->detailByKode($kode);
        if (! $row) {
            return redirect()->to('/cek-booking')->with('error', 'Booking tidak ditemukan.');
        }
        return view('public/booking_sukses', ['booking' => $row]);
    }
}
