<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class FeePayment extends Model
{
    use HasFactory;

    /**
     * Note: Reversal audit columns (`status`, `reversed_amount`, `reversed_at`,
     * `reversed_by`, `reversal_reason`, `reversal_reference`) are deliberately
     * excluded from mass-assignment to protect ledger integrity.
     */
    protected $fillable = [
        'receipt_no',
        'fee_invoice_id',
        'student_id',
        'branch_id',
        'paid_amount',
        'fine_amount',
        'payment_date',
        'payment_method',
        'transaction_id',
        'note',
        'collected_by',
    ];

    protected $casts = [
        'fee_invoice_id' => 'integer',
        'student_id' => 'integer',
        'branch_id' => 'integer',
        'collected_by' => 'integer',
        'reversed_by' => 'integer',
        'reversed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (FeePayment $payment): void {
            $schema = Schema::connection($payment->getConnectionName());
            $table = $payment->getTable();

            // Guard against pre-migration schema state before Migration 3 executes.
            if (empty($payment->branch_id) && !empty($payment->fee_invoice_id) && $schema->hasColumn($table, 'branch_id')) {
                $invoice = FeeInvoice::find($payment->fee_invoice_id);
                if ($invoice && !empty($invoice->branch_id)) {
                    $payment->branch_id = $invoice->branch_id;
                }
            }

            if (empty($payment->status) && $schema->hasColumn($table, 'status')) {
                $payment->status = 'Active';
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function admissionHeadAllocations(): HasMany
    {
        return $this->hasMany(AdmissionPaymentHeadAllocation::class, 'fee_payment_id');
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

    public function fineAmountDecimal(): string
    {
        $raw = $this->attributes['fine_amount'] ?? '0';
        if ($raw === null || $raw === '' || !is_numeric((string) $raw)) {
            return '0.00';
        }

        $normalized = bcadd((string) $raw, '0.00', 2);

        return bccomp($normalized, '0.00', 2) > 0 ? $normalized : '0.00';
    }

    public function tuitionAmountDecimal(): string
    {
        $paid = bcadd((string) ($this->paid_amount ?? '0'), '0.00', 2);
        $fine = $this->fineAmountDecimal();
        $tuition = bcsub($paid, $fine, 2);

        return bccomp($tuition, '0.00', 2) > 0 ? $tuition : '0.00';
    }

    /**
     * Deterministic Tuition-First allocation split for embedded late fines:
     * tuition (`net_amount`) is settled before embedded `fine_amount`.
     * For full payments (`pay_amount == due_amount`), tuition and fine settle simultaneously.
     */
    public static function computeFinePortionForPayment(
        string $netAmount,
        string $fineAmount,
        string $priorActivePaid,
        string $payAmount
    ): string {
        $net = bcadd($netAmount, '0.00', 2);
        $fine = bcadd($fineAmount, '0.00', 2);
        $prior = bcadd($priorActivePaid, '0.00', 2);
        $pay = bcadd($payAmount, '0.00', 2);

        if (bccomp($fine, '0.00', 2) <= 0 || bccomp($pay, '0.00', 2) <= 0) {
            return '0.00';
        }

        $priorTuitionPaid = bccomp($prior, $net, 2) <= 0 ? $prior : $net;
        $unpaidTuitionRaw = bcsub($net, $priorTuitionPaid, 2);
        $unpaidTuition = bccomp($unpaidTuitionRaw, '0.00', 2) > 0 ? $unpaidTuitionRaw : '0.00';

        $rawFinePaid = bcsub($pay, $unpaidTuition, 2);
        if (bccomp($rawFinePaid, '0.00', 2) <= 0) {
            return '0.00';
        }

        $priorFinePaid = bccomp($prior, $net, 2) > 0 ? bcsub($prior, $net, 2) : '0.00';
        $unpaidFineRaw = bcsub($fine, $priorFinePaid, 2);
        $unpaidFine = bccomp($unpaidFineRaw, '0.00', 2) > 0 ? $unpaidFineRaw : '0.00';

        if (bccomp($unpaidFine, '0.00', 2) <= 0) {
            return '0.00';
        }

        return bccomp($rawFinePaid, $unpaidFine, 2) <= 0 ? $rawFinePaid : $unpaidFine;
    }

    /**
     * Extract the canonical master receipt number from child bulk/single receipt strings.
     * Supports `REC-BULK-YYYYMMDD-XXXX[-INVOICEID]` and `REC-YYYYMMDD-XXXX[-INVOICEID]`,
     * while preserving custom/legacy receipt codes intact.
     */
    public static function extractMasterReceiptNo(?string $receiptNo): string
    {
        $normalized = trim((string) $receiptNo);
        if ($normalized === '') {
            return 'N/A';
        }

        if (preg_match('/^(REC-BULK-\d{8}-\d+)(?:-\d+)?$/i', $normalized, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^(REC-\d{8}-\d+)(?:-\d+)?$/i', $normalized, $matches)) {
            return $matches[1];
        }

        return $normalized;
    }

    /**
     * Determine if a payment receipt_no belongs strictly to the requested master receipt key
     * (either an exact match or a numeric invoice suffix `<master>-<invoice_id>`).
     */
    public function belongsToMasterReceipt(string $targetReceiptNo): bool
    {
        $target = trim($targetReceiptNo);
        $current = trim((string) $this->receipt_no);

        if ($target === '' || $current === '') {
            return false;
        }

        if (strcasecmp($current, $target) === 0) {
            return true;
        }

        if (preg_match('/^' . preg_quote($target, '/') . '-(\d+)$/i', $current) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Boundary-safe query scope that prevents LIKE wildcard expansion (`%`, `_`)
     * and prefix collisions (e.g. `REC-20261118-0001` never matches `REC-20261118-00010`).
     */
    public function scopeForMasterReceipt(Builder $query, string $receiptNo): Builder
    {
        $target = trim($receiptNo);
        if ($target === '') {
            return $query->whereRaw('1 = 0');
        }

        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $target);

        return $query->where(function (Builder $sub) use ($target, $escaped): void {
            $sub->where('receipt_no', '=', $target)
                ->orWhereRaw("receipt_no LIKE ? ESCAPE '!'", [$escaped . '-%']);
        });
    }
}