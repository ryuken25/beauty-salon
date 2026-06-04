<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Services\BookingService;
use RuntimeException;

/**
 * Public "cek booking" flow — kode booking saja (dikirim via email setelah
 * booking sukses). Buang dependency nomor HP & WhatsApp template.
 *
 * Keamanan (policy B): jalur publik bersifat read-only. Tombol "Batalkan"
 * di /cek-booking hanya muncul jika pelanggan sudah login (lalu jalur
 * pembatalan dialihkan ke /pelanggan/booking/{kode}/batal yang
 * mem-validasi ownership via session('user_id')). Untuk percobaan kode
 * salah, terapkan rate-limit ringan (5 kegagalan / 15 menit / IP) supaya
 * enumerasi brute-force jadi mahal walaupun format SW-YYYYMMDD-NNN
 * sekuensial.
 */
class CekBooking extends BaseController
{
    private const CANCELABLE = ['pending_verification', 'accepted'];
    private const MAX_FAIL_PER_WINDOW = 5;
    private const WINDOW_SECONDS = 900;

    /** Form (kode booking) + tampilkan 1 booking jika ditemukan. */
    public function index()
    {
        $data = ['kode' => '', 'booking' => null];

        if ($this->request->getMethod() === 'POST') {
            $ipKey = 'cek_fail_' . md5($this->request->getIPAddress());
            $fails = (int) (cache($ipKey) ?? 0);
            if ($fails >= self::MAX_FAIL_PER_WINDOW) {
                return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan kode salah. Silakan coba lagi nanti, atau login untuk melihat semua booking Anda.');
            }
            $kode = strtoupper(trim((string) $this->request->getPost('kode_booking')));
            $data['kode'] = $kode;
            if ($kode === '') {
                return redirect()->back()->withInput()->with('error', 'Masukkan kode booking Anda.');
            }
            $row = (new BookingModel())->detailByKode($kode);
            if (! $row) {
                cache()->save($ipKey, $fails + 1, self::WINDOW_SECONDS);
                return redirect()->back()->withInput()->with('error', 'Booking dengan kode tersebut tidak ditemukan. Cek kembali email konfirmasi Anda.');
            }
            $data['booking'] = $row;
        }
        return view('cek_booking/index', $data);
    }

    /** Halaman konfirmasi batal (publik, walk-in/no-account). */
    public function konfirmasiBatal(string $kode)
    {
        $booking = $this->byKode($kode);
        if ($booking === null) {
            return redirect()->to('/cek-booking')->with('error', 'Booking tidak ditemukan.');
        }
        if (! in_array($booking['status'], self::CANCELABLE, true)) {
            return redirect()->to('/cek-booking')->with('error', 'Booking ini sudah final dan tidak dapat dibatalkan.');
        }
        return view('cek_booking/konfirmasi_batal', ['booking' => $booking]);
    }

    public function prosesBatal(string $kode)
    {
        $booking = $this->byKode($kode);
        if ($booking === null) {
            return redirect()->to('/cek-booking')->with('error', 'Booking tidak ditemukan.');
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
        return redirect()->to('/cek-booking-sukses/' . rawurlencode(strtoupper($kode)));
    }

    public function suksesBatal(string $kode)
    {
        $booking = $this->byKode($kode);
        if ($booking === null || $booking['status'] !== 'cancelled') {
            return redirect()->to('/cek-booking')->with('error', 'Data pembatalan tidak ditemukan.');
        }
        return view('cek_booking/sukses_batal', ['booking' => $booking]);
    }

    private function byKode(string $kode): ?array
    {
        $kode = strtoupper(trim($kode));
        if ($kode === '') return null;
        return (new BookingModel())->detailByKode($kode) ?: null;
    }
}
