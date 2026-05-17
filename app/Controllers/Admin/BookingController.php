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
        $db = db_connect();
        $builder = $db->table('bookings b')
            ->select('b.id, b.kode_booking, b.nama_pelanggan, b.nomor_hp_pelanggan, b.tanggal, b.slot_mulai, b.slot_selesai, b.status, b.sumber, l.nama AS nama_layanan, s.nama AS nama_stylist')
            ->join('layanan l', 'l.id = b.layanan_id')
            ->join('stylists s', 's.id = b.stylist_id');
        $status = (string) $this->request->getGet('status');
        if ($status) $builder->where('b.status', $status);
        $tanggal = (string) $this->request->getGet('tanggal');
        if ($tanggal) $builder->where('b.tanggal', $tanggal);
        $q = (string) $this->request->getGet('q');
        if ($q) $builder->groupStart()->like('b.nama_pelanggan', $q)->orLike('b.kode_booking', $q)->orLike('b.nomor_hp_pelanggan', $q)->groupEnd();
        $rows = $builder->orderBy('b.tanggal', 'DESC')->orderBy('b.slot_mulai', 'DESC')->limit(200)->get()->getResultArray();
        return view('admin/booking/index', ['rows' => $rows, 'filter_status' => $status, 'filter_tanggal' => $tanggal, 'filter_q' => $q]);
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

        if (! $manualMode) {
            $rules = [
                'additional_price' => 'permit_empty|numeric|greater_than_equal_to[0]|less_than[999999999]',
                'metode_bayar'     => 'required|in_list[cash,transfer,qris]',
                'note'             => 'permit_empty|max_length[500]',
            ];
            if (! $this->validate($rules)) {
                return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
            }
        }

        $additionalPrice = (int) ($this->request->getPost('additional_price') ?? 0);
        $note = trim((string) $this->request->getPost('note'));

        // Cross-field rule: when additional_price > 0, note is required.
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
                $manualMode
            );
            $msg = $manualMode
                ? 'Booking diselesaikan secara manual (tanpa pencatatan transaksi).'
                : 'Booking selesai dan transaksi otomatis dibuat.';
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

    public function walkin()
    {
        if ($this->request->getMethod() === 'POST') {
            try {
                $row = (new BookingService())->create([
                    'nama_pelanggan' => $this->request->getPost('nama_pelanggan'),
                    'nomor_hp_pelanggan' => $this->request->getPost('nomor_hp_pelanggan'),
                    'layanan_id' => (int) $this->request->getPost('layanan_id'),
                    'tanggal' => $this->request->getPost('tanggal'),
                    'slot_mulai' => $this->request->getPost('slot_mulai'),
                    'catatan' => $this->request->getPost('catatan'),
                    'sumber' => 'walkin',
                    'verified_via' => 'dashboard:' . session('user_id'),
                    'actor' => 'dashboard:' . session('user_id'),
                    'actor_role' => 'admin',
                ]);
                return redirect()->to('/admin/booking/' . $row['id'])->with('success', 'Walk-in booking berhasil dibuat.');
            } catch (RuntimeException $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }
        $layanans = (new LayananModel())->where('is_active', 1)->orderBy('nama')->find();
        $slot = new SlotService();
        $allSlots = $slot->allSlots();
        $rangeHari = $slot->rangeHari();
        $dates = [];
        for ($i = 0; $i <= $rangeHari; $i++) $dates[] = date('Y-m-d', strtotime("+{$i} days"));
        return view('admin/booking/walkin', ['layanans' => $layanans, 'all_slots' => $allSlots, 'dates' => $dates]);
    }

    public function jadwal()
    {
        $tanggal = (string) ($this->request->getGet('tanggal') ?: date('Y-m-d'));
        $allSlots = (new SlotService())->allSlots();
        $rows = db_connect()->table('bookings b')
            ->select('b.id, b.kode_booking, b.nama_pelanggan, b.status, b.slot_mulai, b.slot_selesai, b.jumlah_slot, l.nama AS nama_layanan, s.nama AS nama_stylist, s.id AS stylist_id')
            ->join('layanan l', 'l.id = b.layanan_id')
            ->join('stylists s', 's.id = b.stylist_id')
            ->where('b.tanggal', $tanggal)
            ->whereIn('b.status', ['pending_verification', 'accepted', 'completed'])
            ->orderBy('b.slot_mulai')->get()->getResultArray();
        $stylists = db_connect()->table('stylists')->where('is_active', 1)->orderBy('nama')->get()->getResultArray();
        $slotMap = [];
        foreach ($rows as $r) {
            $start = strtotime($r['slot_mulai']);
            for ($i = 0; $i < (int) $r['jumlah_slot']; $i++) {
                $key = $r['stylist_id'] . '|' . date('H:i', $start + $i * 1800);
                $slotMap[$key] = ['booking' => $r, 'is_start' => $i === 0];
            }
        }
        return view('admin/booking/jadwal', ['tanggal' => $tanggal, 'all_slots' => $allSlots, 'stylists' => $stylists, 'slot_map' => $slotMap]);
    }
}
