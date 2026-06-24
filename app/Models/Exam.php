<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    // এই একটি লাইন অ্যাড করলেই Mass Assignment এরর আর আসবে না
    protected $guarded = []; 

    // সেশন রিলেশনশিপ (যেহেতু আমরা ভিউতে $exam->sessionYear ব্যবহার করেছি)
    public function sessionYear()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }
}