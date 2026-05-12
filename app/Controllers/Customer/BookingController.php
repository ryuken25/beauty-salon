<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Services\BookingService;
use App\Services\SlotService;
use RuntimeException;

class BookingController extends BaseController
{
    public function create()
    {
        $db = db_connect();
        if ($this->request->getMethod() === 'POST') {
            $rules = [
                'customer_name' => 'required|min_length[3]',
                'customer_phone' => 'required|min_length[8]',
                'service_id' => 'required|is_natural_no_zero',
                'stylist_id' => 'required|is_natural_no_zero',
                'booking_date' => 'required',
                'start_time' => 'required',
            ];
            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
            }
            $name = trim((string) $this->request->getPost('customer_name'));
            $phone = $this->normalisePhone((string) $this->request->getPost('customer_phone'));
            try {
                $customerId = $this->findOrCreateCustomer($name, $phone);
                $id = (new BookingService())->createBooking([
                    'customer_id' => $customerId,
                    'service_id' => $this->request->getPost('service_id'),
                    'stylist_id' => $this->request->getPost('stylist_id'),
                    'booking_date' => $this->request->getPost('booking_date'),
                    'start_time' => $this->request->getPost('start_time'),
                    'notes' => $this->request->getPost('notes'),
                    'source' => 'online',
                    'created_by' => null,
                ]);
                $booking = $db->table('bookings')->select('booking_code')->where('id', $id)->get()->getRowArray();
                return redirect()->to('/pelanggan/booking/sukses/' . $id . '/' . $booking['booking_code']);
            } catch (RuntimeException $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }
        return view('customer/booking_form', [
            'services' => $db->table('services')->where('is_active', 1)->get()->getResultArray(),
            'stylists' => $db->table('stylists')->where('is_active', 1)->get()->getResultArray(),
        ]);
    }

    public function slots()
    {
        return $this->response->setJSON((new SlotService())->generateAvailableSlots(
            (int) $this->request->getGet('service_id'),
            (int) $this->request->getGet('stylist_id'),
            (string) $this->request->getGet('date')
        ));
    }

    public function success(int $id, string $code)
    {
        $row = $this->bookingByIdCode($id, $code);
        if (! $row) {
            return redirect()->to('/pelanggan/booking/cek')->with('error', 'Booking tidak ditemukan.');
        }
        $owner = db_connect()->table('app_settings')->where('setting_key', 'salon_whatsapp_owner')->get()->getRowArray();
        $fallback = db_connect()->table('app_settings')->where('setting_key', 'salon_whatsapp')->get()->getRowArray();
        $ownerNumber = trim((string) ($owner['setting_value'] ?? ($fallback['setting_value'] ?? '')));
        return view('customer/booking_success', ['booking' => $row, 'owner_whatsapp' => $ownerNumber]);
    }

    public function lookup()
    {
        $db = db_connect();
        $booking = null;
        $logs = [];
        if ($this->request->getMethod() === 'POST') {
            $code = strtoupper(trim((string) $this->request->getPost('booking_code')));
            $phone = $this->normalisePhone((string) $this->request->getPost('customer_phone'));
            $row = $db->table('bookings b')
                ->select('b.*, c.name customer_name, c.phone customer_phone, s.name service_name, s.duration_minutes, st.name stylist_name')
                ->join('customers c', 'c.id=b.customer_id')
                ->join('services s', 's.id=b.service_id')
                ->join('stylists st', 'st.id=b.stylist_id')
                ->where('b.booking_code', $code)
                ->get()->getRowArray();
            if ($row && $this->normalisePhone((string) $row['customer_phone']) === $phone) {
                $booking = $row;
                $logs = (new \App\Models\BookingLogModel())->forBooking((int) $row['id']);
            } else {
                return view('customer/booking_lookup', ['booking' => null, 'logs' => [], 'error' => 'Kode booking atau nomor WhatsApp tidak cocok.']);
            }
        }
        return view('customer/booking_lookup', ['booking' => $booking, 'logs' => $logs, 'error' => null]);
    }

    public function cancel(int $id)
    {
        $code = strtoupper(trim((string) $this->request->getPost('booking_code')));
        $phone = $this->normalisePhone((string) $this->request->getPost('customer_phone'));
        $row = $this->bookingByIdCode($id, $code);
        if (! $row || $this->normalisePhone((string) ($row['customer_phone'] ?? '')) !== $phone) {
            return redirect()->to('/pelanggan/booking/cek')->with('error', 'Booking tidak ditemukan atau nomor tidak cocok.');
        }
        try {
            (new BookingService())->cancel($id, null, true);
            return redirect()->to('/pelanggan/booking/cek')->with('success', 'Booking berhasil dibatalkan.');
        } catch (RuntimeException $e) {
            return redirect()->to('/pelanggan/booking/cek')->with('error', $e->getMessage());
        }
    }

    private function bookingByIdCode(int $id, string $code): ?array
    {
        $row = db_connect()->table('bookings b')
            ->select('b.*, c.name customer_name, c.phone customer_phone, s.name service_name, s.duration_minutes, st.name stylist_name')
            ->join('customers c', 'c.id=b.customer_id')
            ->join('services s', 's.id=b.service_id')
            ->join('stylists st', 'st.id=b.stylist_id')
            ->where('b.id', $id)
            ->where('b.booking_code', strtoupper($code))
            ->get()->getRowArray();
        return $row ?: null;
    }

    private function findOrCreateCustomer(string $name, string $phone): int
    {
        $db = db_connect();
        $existing = $db->table('customers')->where('phone', $phone)->orderBy('id', 'DESC')->get()->getRowArray();
        if ($existing) {
            if ($existing['name'] !== $name) {
                $db->table('customers')->where('id', $existing['id'])->update(['name' => $name, 'updated_at' => date('Y-m-d H:i:s')]);
            }
            return (int) $existing['id'];
        }
        $now = date('Y-m-d H:i:s');
        $db->table('customers')->insert([
            'user_id' => null,
            'name' => $name,
            'phone' => $phone,
            'address' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $db->insertID();
    }

    private function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }
        return $digits;
    }
}
