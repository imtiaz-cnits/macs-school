<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_no', 
        'student_id', 
        'fee_setup_id', 
        'amount', 
        'discount', 
        'net_amount', 
        'paid_amount', 
        'due_amount', 
        'status', 
        'due_date',
        'user_id' 
    ];

    public function student() { 
        return $this->belongsTo(Student::class); 
    }
    
    public function feeSetup() { 
        return $this->belongsTo(FeeSetup::class); 
    }
    
    public function payments() { 
        return $this->hasMany(FeePayment::class); 
    }

    
    public function user() { 
        return $this->belongsTo(User::class); 
    }
}