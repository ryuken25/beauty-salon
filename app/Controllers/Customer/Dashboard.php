<?php
namespace App\Controllers\Customer;
use App\Controllers\BaseController;
class Dashboard extends BaseController { public function index() { $db=db_connect(); $customer=$db->table('customers')->where('user_id',session('user_id'))->get()->getRowArray(); $bookings=$customer?$db->table('bookings')->where('customer_id',$customer['id'])->orderBy('created_at','DESC')->limit(5)->get()->getResultArray():[]; return view('customer/dashboard',['bookings'=>$bookings]); } }
