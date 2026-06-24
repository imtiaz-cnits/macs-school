<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAcademicHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'session_year_id',
        'class_id',
        'section_id',
        'roll_number',
        'total_marks',
        'cgpa_or_grade',
        'status',
    ];

    // স্টুডেন্টের সাথে রিলেশন
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}