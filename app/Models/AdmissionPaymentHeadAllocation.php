<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only payment allocation breakdown mapping a FeePayment receipt line item
 * to a specific AdmissionInvoiceHeadAllocation. Historical records must never be deleted.
 */
class AdmissionPaymentHeadAllocation extends Model
{
    use HasFactory;

    protected $table = 'admission_payment_head_allocations';

    /**
     * Reversal metadata (`reversed_at`, `reversal_reference`) is deliberately excluded from
     * mass-assignment to guarantee transitions occur strictly through audited reversal transactions.
     */
    protected $fillable = [
        'fee_payment_id',
        'fee_invoice_id',
        'student_id',
        'branch_id',
        'admission_invoice_head_allocation_id',
        'fee_category_id',
        'fee_category_name_snapshot',
        'head_name_snapshot',
        'allocated_paid_amount',
        'status',
    ];

    protected $casts = [
        'fee_payment_id' => 'integer',
        'fee_invoice_id' => 'integer',
        'student_id' => 'integer',
        'branch_id' => 'integer',
        'admission_invoice_head_allocation_id' => 'integer',
        'fee_category_id' => 'integer',
        'allocated_paid_amount' => 'decimal:2',
        'reversed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new LogicException(
                'Historical AdmissionPaymentHeadAllocation records are immutable and must never be deleted. Use full reversal transitions instead.'
            );
        });
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class, 'fee_payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function invoiceHeadAllocation(): BelongsTo
    {
        return $this->belongsTo(AdmissionInvoiceHeadAllocation::class, 'admission_invoice_head_allocation_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeReversed($query)
    {
        return $query->where('status', 'Reversed');
    }

    public function isReversed(): bool
    {
        return ($this->status ?? 'Active') === 'Reversed';
    }
}
