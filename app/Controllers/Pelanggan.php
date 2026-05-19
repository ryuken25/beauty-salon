<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Services\BookingService;
use RuntimeException;

class Pelanggan extends BaseController
{
    public function dashboard()
    {
        $userId = (int) session('user_id');
        $hp = (string) session('user_hp');
        $bookings = (new BookingModel())->findByUserOrHp($userId, $hp);
        return view('pelanggan/dashboard', [
            'bookings' => $bookings,
            'nama' => session('user_nama'),
        ]);
    }

    public function cancel(int $id)
    {
        $userId = (int) session('user_id');
        $hp = (string) session('user_hp');

        $booking = (new BookingModel())->detail($id);
        if (! $booking) {
            return redirect()->to('/pelanggan/dashboard')->with('error', 'Booking tidak ditemukan.');
        }
        // Ownership check: either matches the registered user_id OR the registered HP.
        $owns = ((int) ($booking['user_id'] ?? 0) === $userId)
            || ((string) ($booking['nomor_hp_pelanggan'] ?? '') === $hp);
        if (! $owns) {
            return redirect()->to('/pelanggan/dashboard')->with('error', 'Booking ini bukan milik Anda.');
        }
        if (! in_array($booking['status'], ['pending_verification', 'accepted'], true)) {
            return redirect()->to('/pelanggan/dashboard')->with('error', 'Booking ini sudah berstatus final dan tidak bisa dibatalkan.');
        }

        if (! $this->validate([
            'cancellation_reason' => 'permit_empty|max_length[500]',
        ])) {
            return redirect()->to('/pelanggan/dashboard')->with('error', implode(' ', $this->validator->getErrors()));
        }
        $reason = trim((string) $this->request->getPost('cancellation_reason'));

        try {
            (new BookingService())->cancel($id, 'pelanggan', $userId, $reason !== '' ? $reason : null);
            return redirect()->to('/pelanggan/dashboard')->with('success', 'Booking ' . $booking['kode_booking'] . ' berhasil dibatalkan.');
        } catch (RuntimeException $e) {
            return redirect()->to('/pelanggan/dashboard')->with('error', $e->getMessage());
        }
    }
}
