<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeSetup extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', 
        'class_id', 
        'session_year_id', 
        'fee_category_id', 
        'amount', 
        'fee_month', 
        'user_id', 
        'status'
    ];

    public function category() { 
        return $this->belongsTo(FeeCategory::class, 'fee_category_id'); 
    }
    
    public function schoolClass() { 
        return $this->belongsTo(Classes::class, 'class_id'); 
    }

    // ইউজারের সাথে রিলেশনশিপ (যে তৈরি করেছে)
    public function user() { 
        return $this->belongsTo(User::class); 
    }
    // ব্রাঞ্চের সাথে সম্পর্ক
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // সেশনের সাথে সম্পর্ক
    public function sessionYear()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }
}