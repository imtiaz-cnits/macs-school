<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    // যে ইউজার অ্যাকাউন্ট দিয়ে শিক্ষক লগইন করবেন
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // যে অ্যাডমিন এই শিক্ষককে সিস্টেমে অ্যাড করেছেন
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}