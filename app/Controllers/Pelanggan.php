<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Services\BookingService;
use RuntimeException;

/**
 * Logged-in customer area. Ownership tervalidasi via session('user_id')
 * (bukan via nomor HP / kode publik), supaya jalur ini tetap aman walaupun
 * /cek-booking publik hanya minta kode booking.
 */
class Pelanggan extends BaseController
{
    private const CANCELABLE = ['pending_verification', 'accepted'];

    public function dashboard()
    {
        $userId = (int) session('user_id');
        $bookings = (new BookingModel())->findByUserId($userId);
        return view('pelanggan/dashboard', [
            'bookings' => $bookings,
            'nama' => session('user_nama'),
        ]);
    }

    public function detail(string $kode)
    {
        $booking = $this->owned($kode);
        if ($booking === null) {
            return redirect()->to('/pelanggan/dashboard')->with('error', 'Booking tidak ditemukan.');
        }
        return view('pelanggan/booking_detail', ['booking' => $booking]);
    }

    public function konfirmasiBatal(string $kode)
    {
        $booking = $this->owned($kode);
        if ($booking === null) {
            return redirect()->to('/pelanggan/dashboard')->with('error', 'Booking tidak ditemukan.');
        }
        if (! in_array($booking['status'], self::CANCELABLE, true)) {
            return redirect()->to('/pelanggan/booking/' . rawurlencode($kode))->with('error', 'Booking ini sudah final dan tidak dapat dibatalkan.');
        }
        return view('pelanggan/booking_konfirmasi_batal', ['booking' => $booking]);
    }

    public function prosesBatal(string $kode)
    {
        $booking = $this->owned($kode);
        if ($booking === null) {
            return redirect()->to('/pelanggan/dashboard')->with('error', 'Booking tidak ditemukan.');
        }
        if (! in_array($booking['status'], self::CANCELABLE, true)) {
            return redirect()->to('/pelanggan/booking/' . rawurlencode($kode))->with('error', 'Booking ini sudah final.');
        }
        if (! $this->validate(['cancellation_reason' => 'permit_empty|max_length[500]'])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $reason = trim((string) $this->request->getPost('cancellation_reason'));
        try {
            (new BookingService())->cancel(
                (int) $booking['id'],
                'pelanggan',
                (int) session('user_id'),
                $reason !== '' ? $reason : null
            );
        } catch (RuntimeException $e) {
            return redirect()->to('/pelanggan/booking/' . rawurlencode($kode))->with('error', $e->getMessage());
        }
        return redirect()->to('/pelanggan/booking/' . rawurlencode($kode) . '/sukses');
    }

    public function suksesBatal(string $kode)
    {
        $booking = $this->owned($kode);
        if ($booking === null || $booking['status'] !== 'cancelled') {
            return redirect()->to('/pelanggan/dashboard')->with('error', 'Data pembatalan tidak ditemukan.');
        }
        // Reuse view publik (tanpa WA) — sama untuk pelanggan login & publik.
        return view('cek_booking/sukses_batal', ['booking' => $booking]);
    }

    /** Booking milik akun yang sedang login, atau null. */
    private function owned(string $kode): ?array
    {
        $row = (new BookingModel())->detailByKode(strtoupper(trim($kode)));
        if (! $row || (int) ($row['user_id'] ?? 0) !== (int) session('user_id')) {
            return null;
        }
        return $row;
    }
}
