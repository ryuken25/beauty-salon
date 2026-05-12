<?php
$tanggalLabel = date('d/m/Y', strtotime($booking['tanggal']));
$slotMulai = substr($booking['slot_mulai'], 0, 5);
$slotSelesai = substr($booking['slot_selesai'], 0, 5);
echo "Halo SW Beauty Salon, saya {$booking['nama_pelanggan']} sudah melakukan booking:\n\n"
    . "• Kode: {$booking['kode_booking']}\n"
    . "• Layanan: {$booking['nama_layanan']}\n"
    . "• Tanggal: {$tanggalLabel}\n"
    . "• Jam: {$slotMulai} – {$slotSelesai}\n\n"
    . "Mohon konfirmasi. Terima kasih.";
