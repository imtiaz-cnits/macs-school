<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    // স্টুডেন্টের সাথে রিলেশন (যাতে রিপোর্টে স্টুডেন্টের নাম দেখানো যায়)
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}