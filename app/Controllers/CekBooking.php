<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Services\BookingService;
use App\Services\WhatsAppTemplateService;
use RuntimeException;

/**
 * Public "cek & batal booking" flow — customers have no account, so every
 * step re-verifies ownership with (kode_booking + nomor HP). The cancel
 * confirmation is a dedicated page (not a modal), per the 2026-05-20 brief.
 */
class CekBooking extends BaseController
{
    private const CANCELABLE = ['pending_verification', 'accepted'];

    /** Form (nomor WA only) + on POST a list of every booking on that number. */
    public function index()
    {
        $data = ['phone' => '', 'bookings' => null];

        if ($this->request->getMethod() === 'POST') {
            $phone = (new BookingService())->normalizePhone((string) $this->request->getPost('nomor_hp'));
            $data['phone'] = $phone;
            if ($phone === '') {
                return redirect()->back()->withInput()->with('error', 'Nomor WhatsApp tidak valid.');
            }
            $rows = (new BookingModel())->findByNomorHp($phone);
            $data['bookings'] = $rows;
            if (empty($rows)) {
                return redirect()->back()->withInput()->with('error', 'Tidak ada booking untuk nomor ini.');
            }
        }
        return view('cek_booking/index', $data);
    }

    /** Dedicated confirmation page. */
    public function konfirmasiBatal(string $kode)
    {
        $phone = (new BookingService())->normalizePhone((string) $this->request->getGet('no_hp'));
        $booking = $this->verified(strtoupper($kode), $phone);
        if ($booking === null) {
            return redirect()->to('/cek-booking')->with('error', 'Booking tidak ditemukan atau nomor HP tidak cocok.');
        }
        if (! in_array($booking['status'], self::CANCELABLE, true)) {
            return redirect()->to('/cek-booking')->with('error', 'Booking ini sudah final dan tidak dapat dibatalkan.');
        }
        return view('cek_booking/konfirmasi_batal', ['booking' => $booking, 'phone' => $phone]);
    }

    /** Process the cancellation, then redirect to the success page. */
    public function prosesBatal(string $kode)
    {
        $kode = strtoupper($kode);
        $phone = (new BookingService())->normalizePhone((string) $this->request->getPost('nomor_hp'));
        $booking = $this->verified($kode, $phone);
        if ($booking === null) {
            return redirect()->to('/cek-booking')->with('error', 'Booking tidak ditemukan atau nomor HP tidak cocok.');
        }
        if (! in_array($booking['status'], self::CANCELABLE, true)) {
            return redirect()->to('/cek-booking')->with('error', 'Booking ini sudah final dan tidak dapat dibatalkan.');
        }
        if (! $this->validate(['cancellation_reason' => 'permit_empty|max_length[500]'])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $reason = trim((string) $this->request->getPost('cancellation_reason'));

        try {
            (new BookingService())->cancel((int) $booking['id'], 'pelanggan', null, $reason !== '' ? $reason : null);
        } catch (RuntimeException $e) {
            return redirect()->to('/cek-booking')->with('error', $e->getMessage());
        }
        return redirect()->to('/cek-booking-sukses/' . rawurlencode($kode) . '?no_hp=' . urlencode($phone));
    }

    /** Success page — shows the WhatsApp deep link to notify admin. */
    public function suksesBatal(string $kode)
    {
        $phone = (new BookingService())->normalizePhone((string) $this->request->getGet('no_hp'));
        $booking = $this->verified(strtoupper($kode), $phone);
        if ($booking === null || $booking['status'] !== 'cancelled') {
            return redirect()->to('/cek-booking')->with('error', 'Data pembatalan tidak ditemukan.');
        }
        $waText = (new WhatsAppTemplateService())->customerCancellationMessage($booking, $booking['cancellation_reason'] ?? null);
        $waLink = (new WhatsAppTemplateService())->ownerLink($waText);
        return view('cek_booking/sukses_batal', ['booking' => $booking, 'wa_link' => $waLink, 'wa_text' => $waText]);
    }

    /** Returns the joined booking row only when kode + phone both match. */
    private function verified(string $kode, string $phone): ?array
    {
        if ($kode === '' || $phone === '') {
            return null;
        }
        $row = (new BookingModel())->detailByKode($kode);
        if (! $row || (string) $row['nomor_hp_pelanggan'] !== $phone) {
            return null;
        }
        return $row;
    }
}
