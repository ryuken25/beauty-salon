<?php

namespace App\Services;

use App\Models\SettingModel;

class WhatsAppTemplateService
{
    public function render(string $type, array $booking): string
    {
        $key = match ($type) {
            'accepted' => 'template_wa_diterima',
            'rejected' => 'template_wa_ditolak',
            'reminder' => 'template_wa_reminder',
            'completed' => 'template_wa_selesai',
            default => 'template_wa_diterima',
        };
        $template = (new SettingModel())->getValue($key, '');
        $vars = [
            '{nama}' => $booking['nama_pelanggan'] ?? '',
            '{kode}' => $booking['kode_booking'] ?? '',
            '{layanan}' => $booking['nama_layanan'] ?? '',
            '{tanggal}' => isset($booking['tanggal']) ? date('d/m/Y', strtotime($booking['tanggal'])) : '',
            '{jam_mulai}' => isset($booking['slot_mulai']) ? substr($booking['slot_mulai'], 0, 5) : '',
            '{jam_selesai}' => isset($booking['slot_selesai']) ? substr($booking['slot_selesai'], 0, 5) : '',
            '{nominal}' => isset($booking['harga_layanan']) ? 'Rp ' . number_format((int) $booking['harga_layanan'], 0, ',', '.') : '',
            '{nomor_owner}' => (new SettingModel())->getValue('nomor_hp_owner', ''),
        ];
        return strtr($template, $vars);
    }

    public function link(string $phone, string $message): string
    {
        return 'https://wa.me/' . preg_replace('/\D+/', '', $phone) . '?text=' . rawurlencode($message);
    }

    public function ownerLink(string $message): string
    {
        $owner = (new SettingModel())->getValue('nomor_hp_owner', '');
        return $owner ? $this->link($owner, $message) : '#';
    }

    public function ownerConfirmationMessage(array $booking): string
    {
        $tanggal = date('d/m/Y', strtotime($booking['tanggal']));
        $mulai = substr($booking['slot_mulai'], 0, 5);
        $selesai = substr($booking['slot_selesai'], 0, 5);
        return "Halo SW Beauty Salon, saya {$booking['nama_pelanggan']} sudah melakukan booking:\n\n"
            . "• Kode: {$booking['kode_booking']}\n"
            . "• Layanan: {$booking['nama_layanan']}\n"
            . "• Tanggal: {$tanggal}\n"
            . "• Jam: {$mulai} – {$selesai}\n\n"
            . 'Mohon konfirmasi. Terima kasih.';
    }
}
