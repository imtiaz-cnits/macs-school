<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_no', 
        'fee_invoice_id', 
        'student_id', 
        'paid_amount', 
        'payment_date', 
        'payment_method', 
        'transaction_id', 
        'note', 
        'collected_by' 
    ];

    public function invoice() { 
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id'); 
    }
    
    public function student() { 
        return $this->belongsTo(Student::class); 
    }
    
   
    public function collector() { 
        return $this->belongsTo(User::class, 'collected_by'); 
    }
}