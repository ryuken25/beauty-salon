<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Services\BookingService;
use App\Services\SlotService;
use RuntimeException;

class BookingController extends BaseController
{
    private function customer(): array { return db_connect()->table('customers')->where('user_id', session('user_id'))->get()->getRowArray() ?? []; }
    public function create()
    {
        $db = db_connect();
        if ($this->request->getMethod() === 'POST') {
            $customer = $this->customer();
            try {
                $id = (new BookingService())->createBooking(['customer_id' => $customer['id'], 'service_id' => $this->request->getPost('service_id'), 'stylist_id' => $this->request->getPost('stylist_id'), 'booking_date' => $this->request->getPost('booking_date'), 'start_time' => $this->request->getPost('start_time'), 'notes' => $this->request->getPost('notes'), 'source' => 'online', 'created_by' => session('user_id')]);
                return redirect()->to('/pelanggan/booking/' . $id . '/sukses');
            } catch (RuntimeException $e) { return redirect()->back()->withInput()->with('error', $e->getMessage()); }
        }
        return view('customer/booking_form', ['services' => $db->table('services')->where('is_active',1)->get()->getResultArray(), 'stylists' => $db->table('stylists')->where('is_active',1)->get()->getResultArray()]);
    }
    public function slots() { return $this->response->setJSON((new SlotService())->generateAvailableSlots((int)$this->request->getGet('service_id'), (int)$this->request->getGet('stylist_id'), (string)$this->request->getGet('date'))); }
    public function history() { $customer=$this->customer(); $rows=db_connect()->table('bookings b')->select('b.*,s.name service_name,st.name stylist_name')->join('services s','s.id=b.service_id')->join('stylists st','st.id=b.stylist_id')->where('b.customer_id',$customer['id'])->orderBy('b.created_at','DESC')->get()->getResultArray(); return view('customer/history',['bookings'=>$rows]); }
    public function detail(int $id) { $customer=$this->customer(); $row=db_connect()->table('bookings b')->select('b.*,c.name customer_name,c.phone customer_phone,s.name service_name,s.duration_minutes,st.name stylist_name')->join('customers c','c.id=b.customer_id')->join('services s','s.id=b.service_id')->join('stylists st','st.id=b.stylist_id')->where('b.id',$id)->where('b.customer_id',$customer['id'])->get()->getRowArray(); if(!$row) return redirect()->to('/pelanggan/booking')->with('error','Booking tidak ditemukan.'); return view('customer/detail',['booking'=>$row]); }

    public function success(int $id)
    {
        $customer = $this->customer();
        $row = db_connect()->table('bookings b')->select('b.*,c.name customer_name,c.phone customer_phone,s.name service_name,s.duration_minutes,st.name stylist_name')->join('customers c','c.id=b.customer_id')->join('services s','s.id=b.service_id')->join('stylists st','st.id=b.stylist_id')->where('b.id',$id)->where('b.customer_id',$customer['id'] ?? 0)->get()->getRowArray();
        if (! $row) {
            return redirect()->to('/pelanggan/booking')->with('error', 'Booking tidak ditemukan.');
        }
        $owner = db_connect()->table('app_settings')->where('setting_key', 'salon_whatsapp_owner')->get()->getRowArray();
        $fallback = db_connect()->table('app_settings')->where('setting_key', 'salon_whatsapp')->get()->getRowArray();
        $ownerNumber = trim((string) ($owner['setting_value'] ?? ($fallback['setting_value'] ?? '')));
        return view('customer/booking_success', ['booking' => $row, 'owner_whatsapp' => $ownerNumber]);
    }
    public function cancel(int $id) { $customer=$this->customer(); $booking=db_connect()->table('bookings')->where('id',$id)->where('customer_id',$customer['id']??0)->get()->getRowArray(); if(!$booking){ return redirect()->to('/pelanggan/booking')->with('error','Booking tidak ditemukan.'); } try { (new BookingService())->cancel($id, (int)session('user_id'), true); return redirect()->back()->with('success','Booking berhasil dibatalkan.'); } catch (RuntimeException $e) { return redirect()->back()->with('error',$e->getMessage()); } }
}
