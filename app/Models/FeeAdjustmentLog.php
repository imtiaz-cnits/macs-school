<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeAdjustmentLog extends Model
{
    use HasFactory;

    protected $table = 'fee_adjustment_logs';

    protected $fillable = [
        'fee_invoice_id',
        'student_id',
        'user_id',
        'invoice_no',
        'student_identity',
        'mode',
        'scope',
        'adjustment_amount',
        'previous_amount',
        'previous_discount',
        'previous_net_amount',
        'previous_due_amount',
        'new_amount',
        'new_discount',
        'new_net_amount',
        'new_due_amount',
        'paid_amount',
        'reason',
    ];

    /**
     * The invoice that was adjusted.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    /**
     * The student whose fee was adjusted.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * The operator who performed the adjustment.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
