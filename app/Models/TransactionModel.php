<?php
namespace App\Models;
class TransactionModel extends BaseAppModel { protected $table = 'transactions'; protected $allowedFields = ['booking_id','transaction_code','amount','payment_method','payment_note','transaction_date','created_by']; }
