<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses';

    protected $fillable = [
        'voucher_no',
        'expense_category_id',
        'vendor_id',
        'branch_id',
        'expense_date',
        'due_date',
        'amount',
        'paid_amount',
        'payment_status',
        'payment_method',
        'paid_to',
        'reference_no',
        'receipt_file',
        'note',
        'user_id',
    ];

    protected $casts = [
        'expense_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    /**
     * The category this expense belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * The vendor associated with this expense (nullable for historical or ad-hoc expenses).
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(ExpenseVendor::class, 'vendor_id');
    }

    /**
     * Direct payments linked to this expense voucher.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class, 'expense_id');
    }

    /**
     * Payment allocations made against this expense bill.
     */
    public function paymentAllocations(): MorphMany
    {
        return $this->morphMany(VendorPaymentAllocation::class, 'allocatable');
    }

    /**
     * Determine if any canonical payment or allocation exists for this expense.
     */
    public function hasCanonicalPayments(): bool
    {
        return $this->payments()->exists() || $this->paymentAllocations()->exists();
    }

    /**
     * Remaining due amount on this expense voucher.
     */
    public function getDueAmountAttribute(): float
    {
        return max(0.0, round((float) $this->amount - (float) $this->paid_amount, 2));
    }

    /**
     * Determine if this voucher is currently overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_amount > 0.0 && !empty($this->due_date) && $this->due_date->format('Y-m-d') < date('Y-m-d');
    }

    /**
     * Recalculate payment status based on paid amount vs total amount.
     */
    public function updatePaymentStatus(): void
    {
        $due = $this->due_amount;
        if ($due <= 0.0) {
            $this->payment_status = 'Paid';
        } elseif ((float) $this->paid_amount > 0.0) {
            $this->payment_status = 'Partially Paid';
        } else {
            $this->payment_status = 'Unpaid';
        }
        $this->save();
    }

    /**
     * Scope query to outstanding (unpaid or partially paid) vouchers.
     */
    public function scopeOutstanding($query)
    {
        return $query->whereIn('payment_status', ['Unpaid', 'Partially Paid']);
    }

    /**
     * Scope query to overdue vouchers.
     */
    public function scopeOverdue($query)
    {
        return $query->whereIn('payment_status', ['Unpaid', 'Partially Paid'])
                     ->whereNotNull('due_date')
                     ->where('due_date', '<', date('Y-m-d'));
    }

    /**
     * The line items recorded on this expense voucher.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ExpenseItem::class, 'expense_id');
    }

    /**
     * The branch where this expense was incurred.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * The user who recorded this expense.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Audit logs for modifications or deletion of this expense.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(ExpenseAuditLog::class, 'expense_id');
    }

    /**
     * Safely generate a unique voucher number under high concurrency.
     * Uses bounded retries with cryptographic entropy and throws a controlled RuntimeException
     * if maximum retries are exhausted.
     *
     * Format: EXP-YYYYMMDD-XXXXXX (e.g. EXP-20261015-8F3A2B)
     *
     * @param int $maxAttempts Maximum retry attempts before throwing an exception
     * @return string
     * @throws \RuntimeException
     */
    public static function generateVoucherNo(int $maxAttempts = 10): string
    {
        $datePart = date('Ymd');

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $entropy = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $voucherNo = "EXP-{$datePart}-{$entropy}";

            if (!static::where('voucher_no', $voucherNo)->exists()) {
                return $voucherNo;
            }
        }

        throw new \RuntimeException("Failed to generate a unique voucher number after {$maxAttempts} attempts.");
    }

    /**
     * Determine if a private receipt file exists for this expense.
     */
    public function hasReceipt(): bool
    {
        return !empty($this->receipt_file) && \Illuminate\Support\Facades\Storage::disk('local')->exists($this->receipt_file);
    }

    /**
     * Get the authenticated streaming route for the receipt.
     */
    public function getReceiptUrlAttribute(): ?string
    {
        if (empty($this->receipt_file)) {
            return null;
        }

        return route('expenses.receipt.view', $this->id);
    }
}
