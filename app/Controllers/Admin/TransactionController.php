<?php
namespace App\Controllers\Admin;
use App\Controllers\BaseController;
class TransactionController extends BaseController { public function index(){ $rows=db_connect()->table('transactions t')->select('t.*,b.booking_code,c.name customer_name,s.name service_name')->join('bookings b','b.id=t.booking_id')->join('customers c','c.id=b.customer_id')->join('services s','s.id=b.service_id')->orderBy('t.transaction_date','DESC')->get()->getResultArray(); return view('admin/transactions/index',['transactions'=>$rows]); } public function report(){ return $this->index(); } }
