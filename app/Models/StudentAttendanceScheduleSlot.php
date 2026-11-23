<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentAttendanceScheduleSlot extends Model
{
    use HasFactory;

    public const SHIFT_MORNING = 'Morning';
    public const SHIFT_DAY = 'Day';

    protected $table = 'student_attendance_schedule_slots';

    protected $fillable = [
        'schedule_id',
        'slot_name',
        'shift_group',
        'on_time_start',
        'on_time_end',
        'late_end',
        'absent_from',
        'check_out_from',
        'early_grace_minutes',
        'debounce_minutes',
        'min_checkout_gap_minutes',
        'sort_order',
    ];

    protected $casts = [
        'schedule_id'              => 'integer',
        'early_grace_minutes'      => 'integer',
        'debounce_minutes'         => 'integer',
        'min_checkout_gap_minutes' => 'integer',
        'sort_order'               => 'integer',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(StudentAttendanceSchedule::class, 'schedule_id');
    }

    public function slotClasses(): HasMany
    {
        return $this->hasMany(StudentAttendanceScheduleSlotClass::class, 'slot_id');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            Classes::class,
            'student_attendance_schedule_slot_classes',
            'slot_id',
            'class_id'
        )->withPivot('schedule_id')->withTimestamps();
    }
}
