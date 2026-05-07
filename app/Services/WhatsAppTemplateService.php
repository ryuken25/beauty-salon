<?php

namespace App\Services;

class WhatsAppTemplateService
{
    public function message(string $type, array $booking): string
    {
        $nama = $booking['customer_name'] ?? $booking['name'] ?? 'Pelanggan';
        $tanggal = date('d/m/Y', strtotime($booking['booking_date']));
        $mulai = substr($booking['start_time'], 0, 5);
        $selesai = substr($booking['end_time'], 0, 5);
        $harga = number_format((float) ($booking['service_price'] ?? $booking['price'] ?? 0), 0, ',', '.');
        if ($type === 'accepted') {
            return "Halo Kak {$nama}, booking di SW Beauty Salon sudah diterima.\nKode booking: {$booking['booking_code']}\nLayanan: {$booking['service_name']}\nStylist: {$booking['stylist_name']}\nTanggal: {$tanggal}\nJam: {$mulai} - {$selesai}\nTotal: Rp{$harga}\n\nMohon datang sesuai jadwal ya Kak. Terima kasih.";
        }
        if ($type === 'rejected') {
            return "Halo Kak {$nama}, mohon maaf booking di SW Beauty Salon belum bisa diterima.\nKode booking: {$booking['booking_code']}\nLayanan: {$booking['service_name']}\nTanggal: {$tanggal}\nJam: {$mulai}\n\nSilakan pilih jadwal lain yang masih tersedia. Terima kasih.";
        }
        if ($type === 'cancelled') {
            return "Halo Kak {$nama}, booking dengan kode {$booking['booking_code']} telah dibatalkan.\nJika ingin melakukan booking ulang, silakan pilih jadwal yang tersedia di sistem. Terima kasih.";
        }
        return "Halo Kak {$nama}, layanan untuk booking {$booking['booking_code']} telah selesai.\nTerima kasih sudah datang ke SW Beauty Salon.";
    }

    public function link(string $phone, string $message): string
    {
        $phone = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }
}
