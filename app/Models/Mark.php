<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mark extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function sessionYear() {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }

    public function branch() {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // আগের রিলেশনগুলো...
    public function exam() { return $this->belongsTo(Exam::class, 'exam_id'); }
    public function classes() { return $this->belongsTo(Classes::class, 'class_id'); }
    public function student() { return $this->belongsTo(Student::class, 'student_id'); }
    public function subject() { return $this->belongsTo(Subject::class, 'subject_id'); }
}