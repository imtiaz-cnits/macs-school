<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;
    
    // সব ফিল্ড মাস অ্যাসাইনমেন্ট করার অনুমতি (এটি আপনার 500 এরর সমাধান করবে)
    protected $guarded = []; 

    /**
     * Relationships
     */

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // class_id এর মাধ্যমে রিলেশন (আপনার ডাটাবেস অনুযায়ী)
    public function schoolClass()
    {
        return $this->belongsTo(Classes::class, 'class_id'); 
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function sessionYear()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function academicHistories() 
    { 
        return $this->hasMany(StudentAcademicHistory::class); 
    }

    /**
     * Photo Accessor: ছবি না থাকলে ডিফল্ট ছবি দেখাবে
     */
   public function getPhotoAttribute($value)
        {
            if (!$value) {
                // .jpg এর বদলে .png দিন কারণ আপনার ফোল্ডারে ফাইলগুলো .png ফরমেটে আছে
                return $this->gender === 'Male' 
                    ? 'student_photos/boy.png' 
                    : 'student_photos/girl.png';
            }
            return $value;
        }
}