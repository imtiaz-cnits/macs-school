<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendanceScheduleSlotClass extends Model
{
    protected $table = 'student_attendance_schedule_slot_classes';

    protected $fillable = [
        'schedule_id',
        'slot_id',
        'class_id',
    ];

    protected $casts = [
        'schedule_id' => 'integer',
        'slot_id'     => 'integer',
        'class_id'    => 'integer',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(StudentAttendanceSchedule::class, 'schedule_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(StudentAttendanceScheduleSlot::class, 'slot_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}
