<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'branch_id', 'session_year_id', 'class_id', 
        'section_id', 'teacher_id', 'user_id', 'attendance_date', 
        'status', 'remarks'
    ];

    // ১. স্টুডেন্টের সাথে রিলেশন
    public function student() {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // ২. ক্লাসের সাথে রিলেশন
    public function class() {
        return $this->belongsTo(Classes::class, 'class_id'); // আপনার টেবিলের নাম classes হলে Classes মডেল হবে
    }

    // ৩. সেকশনের সাথে রিলেশন
    public function section() {
        return $this->belongsTo(Section::class, 'section_id');
    }

    // ৪. শিক্ষকের সাথে রিলেশন (যে শিক্ষক হাজিরা নিয়েছেন)
    public function teacher() {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}