<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'user_id',
        'previous_status',
        'new_status',
        'action_type',
        'last_active_month',
        'effective_date',
        'reason',
        'reconciled_invoice_ids',
        'reconciled_amount',
        'skipped_invoice_ids',
    ];

    protected $casts = [
        'reconciled_invoice_ids' => 'array',
        'skipped_invoice_ids' => 'array',
        'effective_date' => 'date',
        'reconciled_amount' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
