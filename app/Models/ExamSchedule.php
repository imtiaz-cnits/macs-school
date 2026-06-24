<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    // রিলেশনশিপগুলো দিয়ে রাখলাম, পরে কাজে লাগবে
    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

   public function classes() // schoolClass এর বদলে classes লিখলাম
    {
        return $this->belongsTo(Classes::class, 'class_id'); 
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}