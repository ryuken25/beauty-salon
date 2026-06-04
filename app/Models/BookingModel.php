<?php
namespace App\Models;

class BookingModel extends BaseAppModel
{
    protected $table = 'bookings';
    protected $allowedFields = [
        'kode_booking', 'user_id',
        'nama_pelanggan', 'nomor_hp_pelanggan', 'email_pelanggan',
        'layanan_id',
        'tanggal', 'slot_mulai', 'slot_selesai', 'jumlah_slot',
        'harga_layanan', 'dp_amount', 'dp_proof_path', 'payment_status',
        'email_reminder_sent_at',
        'status', 'sumber', 'catatan',
        'wa_sent', 'verified_via', 'verified_at', 'completed_at', 'cancelled_at', 'cancelled_by',
        'rejection_reason', 'cancellation_reason',
    ];

    public function detail(int $id): ?array
    {
        return $this->joinedBuilder()->where('b.id', $id)->get()->getRowArray() ?: null;
    }

    public function detailByKode(string $kode): ?array
    {
        return $this->joinedBuilder()->where('b.kode_booking', $kode)->get()->getRowArray() ?: null;
    }

    public function findByNomorHp(string $nomorHp): array
    {
        return $this->joinedBuilder()
            ->where('b.nomor_hp_pelanggan', $nomorHp)
            ->orderBy('b.created_at', 'DESC')
            ->limit(50)
            ->get()->getResultArray();
    }

    /** Riwayat booking milik 1 akun pelanggan (dashboard pelanggan). */
    public function findByUserId(int $userId): array
    {
        return $this->joinedBuilder()
            ->where('b.user_id', $userId)
            ->orderBy('b.tanggal', 'DESC')
            ->orderBy('b.slot_mulai', 'DESC')
            ->get()->getResultArray();
    }

    private function joinedBuilder()
    {
        return $this->db->table('bookings b')
            ->select('b.*, l.nama AS nama_layanan, l.kategori AS kategori_layanan, l.durasi_menit, l.harga AS harga_layanan_master, l.ikon AS ikon_layanan')
            ->join('layanan l', 'l.id = b.layanan_id');
    }
}
