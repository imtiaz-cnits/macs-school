<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentAttendanceSchedule extends Model
{
    use HasFactory;

    public const MODE_NORMAL = 'normal';
    public const MODE_EXAM = 'exam';

    protected $table = 'student_attendance_schedules';

    protected $fillable = [
        'name',
        'mode',
        'is_default',
        'is_template',
        'is_active',
        'start_date',
        'end_date',
        'exam_id',
        'branch_id',
        'priority',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'is_default'  => 'boolean',
        'is_template' => 'boolean',
        'is_active'   => 'boolean',
        'start_date'  => 'date:Y-m-d',
        'end_date'    => 'date:Y-m-d',
        'priority'    => 'integer',
        'exam_id'     => 'integer',
        'branch_id'   => 'integer',
        'created_by'  => 'integer',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(StudentAttendanceScheduleSlot::class, 'schedule_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function slotClasses(): HasMany
    {
        return $this->hasMany(StudentAttendanceScheduleSlotClass::class, 'schedule_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_template', false);
    }

    public function scopeDefaultNormal(Builder $query): Builder
    {
        return $query->where('mode', self::MODE_NORMAL)
            ->where('is_default', true)
            ->where('is_active', true)
            ->where('is_template', false);
    }

    public function scopeTemplates(Builder $query): Builder
    {
        return $query->where('is_template', true);
    }

    public function scopeForBranchOrGlobal(Builder $query, ?int $branchId = null): Builder
    {
        if ($branchId === null) {
            return $query->whereNull('branch_id');
        }

        return $query->where(function (Builder $q) use ($branchId): void {
            $q->where('branch_id', $branchId)->orWhereNull('branch_id');
        });
    }

    public function scopeEffectiveOnDate(Builder $query, Carbon|string $date, ?int $branchId = null): Builder
    {
        $dateStr = $date instanceof Carbon
            ? $date->copy()->timezone('Asia/Dhaka')->toDateString()
            : Carbon::parse($date, 'Asia/Dhaka')->toDateString();

        return $query->active()
            ->forBranchOrGlobal($branchId)
            ->where(function (Builder $q) use ($dateStr): void {
                $q->where(function (Builder $examQ) use ($dateStr): void {
                    $examQ->where('mode', self::MODE_EXAM)
                        ->whereNotNull('start_date')
                        ->whereNotNull('end_date')
                        ->whereDate('start_date', '<=', $dateStr)
                        ->whereDate('end_date', '>=', $dateStr);
                })->orWhere(function (Builder $normalQ): void {
                    $normalQ->where('mode', self::MODE_NORMAL)
                        ->where('is_default', true);
                });
            });
    }
}
