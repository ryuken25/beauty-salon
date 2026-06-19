<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BookingLogModel;
use App\Models\BookingModel;
use App\Models\LayananModel;
use App\Services\BookingService;
use App\Services\SlotService;
use App\Services\WhatsAppTemplateService;
use RuntimeException;

class BookingController extends BaseController
{
    public function index()
    {
        // Lazy sweep tick (auto-cancel + due reminders), throttled 5 min.
        // Production should still schedule the two Spark commands.
        if (! cache('notify_tick_last_run')) {
            cache()->save('notify_tick_last_run', time(), 300);
            $svc = new BookingService();
            $svc->autoCancelExpired();
            $svc->sendDueReminders();
        }
        $db = db_connect();
        $builder = $db->table('bookings b')
            ->select('b.id, b.kode_booking, b.nama_pelanggan, b.nomor_hp_pelanggan, b.tanggal, b.slot_mulai, b.slot_selesai, b.status, b.sumber, b.payment_status, l.nama AS nama_layanan')
            ->join('layanan l', 'l.id = b.layanan_id');
        $status = (string) $this->request->getGet('status');
        if ($status) $builder->where('b.status', $status);
        $tanggal = (string) $this->request->getGet('tanggal');
        if ($tanggal) $builder->where('b.tanggal', $tanggal);
        $bulan = (string) $this->request->getGet('bulan');
        if ($bulan && preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            // Range awal–akhir bulan, pakai tanggal booking (bukan created_at).
            $start = $bulan . '-01';
            $end = date('Y-m-t', strtotime($start));
            $builder->where('b.tanggal >=', $start)->where('b.tanggal <=', $end);
        } else {
            $bulan = '';
        }
        $q = (string) $this->request->getGet('q');
        if ($q) $builder->groupStart()->like('b.nama_pelanggan', $q)->orLike('b.kode_booking', $q)->orLike('b.nomor_hp_pelanggan', $q)->groupEnd();
        $rows = $builder->orderBy('b.tanggal', 'DESC')->orderBy('b.slot_mulai', 'DESC')->limit(200)->get()->getResultArray();
        return view('admin/booking/index', [
            'rows' => $rows,
            'filter_status' => $status,
            'filter_tanggal' => $tanggal,
            'filter_bulan' => $bulan,
            'filter_q' => $q,
        ]);
    }

    public function detail(int $id)
    {
        $row = (new BookingModel())->detail($id);
        if (! $row) return redirect()->to('/admin/booking')->with('error', 'Booking tidak ditemukan.');
        $logs = (new BookingLogModel())->forBooking($id);
        $wa = new WhatsAppTemplateService();
        $type = match ($row['status']) {
            'accepted' => 'accepted',
            'rejected' => 'rejected',
            'completed' => 'completed',
            default => 'accepted',
        };
        $message = $wa->render($type, $row);
        $waLink = $wa->link($row['nomor_hp_pelanggan'], $message);
        return view('admin/booking/detail', ['booking' => $row, 'logs' => $logs, 'wa_message' => $message, 'wa_link' => $waLink]);
    }

    public function verify(int $id)
    {
        try {
            (new BookingService())->verify($id, (int) session('user_id'));
            return redirect()->to('/admin/booking/' . $id)->with('success', 'Booking diverifikasi.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reject(int $id)
    {
        try {
            (new BookingService())->reject($id, (int) session('user_id'), $this->request->getPost('rejection_reason'));
            return redirect()->to('/admin/booking/' . $id)->with('success', 'Booking ditolak.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancel(int $id)
    {
        try {
            (new BookingService())->cancel($id, 'admin', (int) session('user_id'));
            return redirect()->to('/admin/booking/' . $id)->with('success', 'Booking dibatalkan.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function complete(int $id)
    {
        $manualMode = (bool) $this->request->getPost('manual_mode');

        $rules = $manualMode
            ? [
                'manual_nominal' => 'required|numeric|greater_than[0]|less_than[999999999]',
                'note'           => 'required|max_length[500]',
                'metode_bayar'   => 'required|in_list[cash,transfer,qris]',
            ]
            : [
                'additional_price' => 'permit_empty|numeric|greater_than_equal_to[0]|less_than[999999999]',
                'note'             => 'permit_empty|max_length[500]',
                'metode_bayar'     => 'required|in_list[cash,transfer,qris]',
            ];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $additionalPrice = (int) ($this->request->getPost('additional_price') ?? 0);
        $manualNominal   = (int) ($this->request->getPost('manual_nominal') ?? 0);
        $note            = trim((string) $this->request->getPost('note'));

        // Cross-field rule (auto mode only): catatan wajib kalau ada biaya tambahan.
        if (! $manualMode && $additionalPrice > 0 && $note === '') {
            return redirect()->back()->with('error', 'Catatan wajib diisi ketika ada biaya tambahan.');
        }

        try {
            (new BookingService())->complete(
                $id,
                (int) session('user_id'),
                (string) ($this->request->getPost('metode_bayar') ?: 'cash'),
                $note !== '' ? $note : null,
                $additionalPrice,
                $manualMode,
                $manualNominal
            );
            $msg = $manualMode
                ? 'Booking selesai. Transaksi dicatat manual.'
                : 'Booking selesai dan transaksi otomatis dibuat.';

            // Check if email_pelanggan is empty to add a warning
            $booking = (new \App\Models\BookingModel())->find($id);
            if ($booking && empty($booking['email_pelanggan'])) {
                session()->setFlashdata('warning', 'Peringatan: Invoice final tidak terkirim via email karena email pelanggan kosong.');
            }

            return redirect()->to('/admin/booking/' . $id)->with('success', $msg);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function waSent(int $id)
    {
        (new BookingService())->markWaSent($id, (int) session('user_id'));
        return redirect()->back()->with('success', 'WhatsApp ditandai sudah dikirim.');
    }

    public function receipt(int $id)
    {
        $row = (new BookingModel())->detail($id);
        if (! $row) {
            return redirect()->to('/admin/booking')->with('error', 'Booking tidak ditemukan.');
        }

        // Ambil nama kasir/admin yang memverifikasi
        $db = db_connect();
        $cashier = 'Admin';
        if (! empty($row['verified_via'])) {
            if (str_starts_with($row['verified_via'], 'dashboard:')) {
                $adminId = (int) substr($row['verified_via'], 10);
                $admin = $db->table('users')->where('id', $adminId)->get()->getRowArray();
                if ($admin) {
                    $cashier = $admin['nama'];
                }
            } else {
                $cashier = $row['verified_via'];
            }
        }

        return view('admin/booking/receipt', [
            'booking' => $row,
            'cashier' => $cashier,
        ]);
    }

    public function resendEmailDp(int $id)
    {
        $row = (new BookingModel())->detail($id);
        if (! $row) {
            return redirect()->back()->with('error', 'Booking tidak ditemukan.');
        }
        if (empty($row['email_pelanggan'])) {
            return redirect()->back()->with('error', 'Pelanggan tidak memiliki alamat email.');
        }

        $sent = (new \App\Services\NotificationService())->sendDpInvoiceEmail($row);
        if ($sent) {
            return redirect()->back()->with('success', 'Email invoice DP berhasil dikirim ulang.');
        }
        return redirect()->back()->with('error', 'Gagal mengirim email. Silakan cek konfigurasi email di .env.');
    }

    public function walkin()
    {
        if ($this->request->getMethod() === 'POST') {
            $rules = [
                'nama_pelanggan' => 'required',
                'nomor_hp_pelanggan' => 'required|regex_match[/^(\+?62|0)8[0-9]{7,12}$/]',
                'email_pelanggan' => 'required|valid_email',
                'layanan_id' => 'required|is_natural_no_zero',
                'tanggal' => 'required|valid_date[Y-m-d]',
                'slot_mulai' => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]',
                'bukti_dp' => 'uploaded[bukti_dp]|is_image[bukti_dp]|mime_in[bukti_dp,image/png,image/jpg,image/jpeg,image/webp]',
            ];
            $errors = [
                'layanan_id' => [
                    'required' => 'Silakan pilih layanan terlebih dahulu.',
                    'is_natural_no_zero' => 'Silakan pilih layanan terlebih dahulu.',
                ],
                'email_pelanggan' => [
                    'required' => 'Alamat email wajib diisi.',
                    'valid_email' => 'Format alamat email tidak valid.',
                ],
                'nomor_hp_pelanggan' => [
                    'required' => 'Nomor HP wajib diisi.',
                    'regex_match' => 'Format nomor HP tidak valid. Gunakan format seperti 081234567890.',
                ],
                'bukti_dp' => [
                    'uploaded' => 'Bukti pembayaran DP wajib diunggah.',
                    'is_image' => 'Bukti pembayaran DP harus berupa file gambar.',
                    'mime_in' => 'Format file gambar tidak valid. Gunakan PNG, JPG, JPEG, atau WEBP.',
                ]
            ];
            if (! $this->validate($rules, $errors)) {
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
                $row = (new BookingService())->create([
                    'nama_pelanggan' => $this->request->getPost('nama_pelanggan'),
                    'nomor_hp_pelanggan' => $this->request->getPost('nomor_hp_pelanggan'),
                    'email_pelanggan' => $this->request->getPost('email_pelanggan'),
                    'layanan_id' => (int) $this->request->getPost('layanan_id'),
                    'tanggal' => $this->request->getPost('tanggal'),
                    'slot_mulai' => $this->request->getPost('slot_mulai'),
                    'catatan' => $this->request->getPost('catatan'),
                    'dp_proof_path' => $proofRelative,
                    'sumber' => 'walkin',
                    'verified_via' => 'dashboard:' . session('user_id'),
                    'actor' => 'dashboard:' . session('user_id'),
                    'actor_role' => 'admin',
                ]);
                return redirect()->to('/admin/booking/' . $row['id'])->with('success', 'Walk-in booking berhasil dibuat.');
            } catch (RuntimeException $e) {
                @unlink($uploadDir . '/' . $name);
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }
        $slot = new SlotService();
        $allSlots = $slot->allSlots();
        $rangeHari = $slot->rangeHari();
        $dates = [];
        for ($i = 0; $i <= $rangeHari; $i++) $dates[] = date('Y-m-d', strtotime("+{$i} days"));
        return view('admin/booking/walkin', [
            'layanans' => (new LayananModel())->where('is_active', 1)->orderBy('nama')->find(),
            'all_slots' => $allSlots,
            'dates' => $dates,
        ]);
    }

    public function jadwal()
    {
        $tanggal = (string) ($this->request->getGet('tanggal') ?: date('Y-m-d'));
        $allSlots = (new SlotService())->allSlots();
        $rows = db_connect()->table('bookings b')
            ->select('b.id, b.kode_booking, b.nama_pelanggan, b.status, b.slot_mulai, b.slot_selesai, b.jumlah_slot, l.nama AS nama_layanan')
            ->join('layanan l', 'l.id = b.layanan_id')
            ->where('b.tanggal', $tanggal)
            ->whereIn('b.status', ['pending_verification', 'accepted', 'completed'])
            ->orderBy('b.slot_mulai')->get()->getResultArray();
        $slotMap = [];
        foreach ($rows as $r) {
            $start = strtotime($r['slot_mulai']);
            for ($i = 0; $i < (int) $r['jumlah_slot']; $i++) {
                $key = date('H:i', $start + $i * 1800);
                $slotMap[$key] = ['booking' => $r, 'is_start' => $i === 0];
            }
        }
        return view('admin/booking/jadwal', ['tanggal' => $tanggal, 'all_slots' => $allSlots, 'slot_map' => $slotMap]);
    }
}
