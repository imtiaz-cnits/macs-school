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

    // ৫. ব্রাঞ্চের সাথে রিলেশন
    public function branch() {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // ৬. সেশনের সাথে রিলেশন
    public function sessionYear() {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }

    /**
     * Parse swipe IN time from remarks string
     */
    public function getInTimeAttribute(): ?string
    {
        if (preg_match('/In:\s*([0-9]{1,2}:[0-9]{2}(?::[0-9]{2})?)/i', $this->remarks ?? '', $matches)) {
            return date('h:i A', strtotime($matches[1]));
        }
        return null;
    }

    /**
     * Parse swipe OUT time from remarks string
     */
    public function getOutTimeAttribute(): ?string
    {
        if (preg_match('/Out:\s*([0-9]{1,2}:[0-9]{2}(?::[0-9]{2})?)/i', $this->remarks ?? '', $matches)) {
            return date('h:i A', strtotime($matches[1]));
        }
        return null;
    }
}