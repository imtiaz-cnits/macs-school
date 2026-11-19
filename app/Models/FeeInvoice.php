<?php

namespace App\Models;

use App\Exceptions\HistoricalSnapshotCorruptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class FeeInvoice extends Model
{
    use HasFactory;

    public const ADMISSION_INVOICE_PREFIX = 'INV-ADM-';
    public const ADMISSION_INVOICE_PATTERN = '/^INV-ADM-(?:S\d+-)?(\d{4})(0[1-9]|1[0-2])-\d+(?:-V\d+)?$/i';
    private const ANY_YEAR_MONTH_INVOICE_PATTERN = '/^INV-(?:ADM-(?:S\d+-)?)?(\d{4})(0[1-9]|1[0-2])-/i';

    /**
     * Note: `admission_active_scope` is a MySQL virtual generated column and must never be fillable.
     */
    protected $fillable = [
        'invoice_no',
        'student_id',
        'branch_id',
        'session_year_id',
        'branch_name_snapshot',
        'fee_setup_id',
        'admission_fee_structure_id',
        'amount',
        'discount',
        'net_amount',
        'paid_amount',
        'due_amount',
        'status',
        'due_date',
        'user_id',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'branch_id' => 'integer',
        'session_year_id' => 'integer',
        'fee_setup_id' => 'integer',
        'admission_fee_structure_id' => 'integer',
        'user_id' => 'integer',
    ];

    protected $appends = [
        'display_status',
    ];

    protected static function booted(): void
    {
        static::creating(function (FeeInvoice $invoice): void {
            // Strictly legacy-only compatibility hook:
            // Admission invoices (NULL fee_setup_id or non-NULL admission_fee_structure_id)
            // MUST NEVER receive legacy FeeSetup-derived values.
            if (empty($invoice->fee_setup_id) || !empty($invoice->admission_fee_structure_id)) {
                return;
            }

            $needsAny = empty($invoice->branch_id) || empty($invoice->session_year_id) || empty($invoice->branch_name_snapshot);
            if (!$needsAny) {
                return;
            }

            // Guard against pre-migration schema state before Migration 3 has been executed.
            $schema = Schema::connection($invoice->getConnectionName());
            if (!$schema->hasColumns($invoice->getTable(), ['branch_id', 'session_year_id', 'branch_name_snapshot'])) {
                return;
            }

            if (empty($invoice->branch_id) || empty($invoice->session_year_id)) {
                $setup = FeeSetup::find($invoice->fee_setup_id);
                if ($setup) {
                    $invoice->branch_id = $invoice->branch_id ?: $setup->branch_id;
                    $invoice->session_year_id = $invoice->session_year_id ?: $setup->session_year_id;
                }
            }

            if (empty($invoice->branch_name_snapshot) && !empty($invoice->branch_id)) {
                $branch = Branch::find($invoice->branch_id);
                if ($branch) {
                    $invoice->branch_name_snapshot = $branch->branch_name;
                }
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function sessionYear(): BelongsTo
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }

    public function admissionFeeStructure(): BelongsTo
    {
        return $this->belongsTo(AdmissionFeeStructure::class, 'admission_fee_structure_id');
    }

    public function admissionHeadAllocations(): HasMany
    {
        return $this->hasMany(AdmissionInvoiceHeadAllocation::class, 'fee_invoice_id')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');
    }

    public function feeSetup(): BelongsTo
    {
        return $this->belongsTo(FeeSetup::class)->withDefault(function (FeeSetup $setup, FeeInvoice $invoice): void {
            if (!$invoice->admission_fee_structure_id) {
                return;
            }

            $firstAlloc = $invoice->relationLoaded('admissionHeadAllocations')
                ? $invoice->admissionHeadAllocations->first()
                : $invoice->admissionHeadAllocations()->first();

            $branchId = $invoice->branch_id ?? $firstAlloc?->branch_id;
            $branchName = $invoice->branch_name_snapshot ?? $firstAlloc?->branch_name_snapshot;
            $categoryId = $firstAlloc?->fee_category_id;
            $categoryName = $firstAlloc?->fee_category_name_snapshot;

            // Strict Historical Snapshot Integrity: zero generic fallbacks allowed.
            if (empty($branchName) || empty($categoryName)) {
                throw new HistoricalSnapshotCorruptException(
                    "Historical Snapshot Integrity Violation: Admission Invoice #{$invoice->invoice_no} (ID: {$invoice->id}) " .
                    "is missing mandatory immutable snapshot metadata (Branch: '{$branchName}', Category: '{$categoryName}')."
                );
            }

            $setup->id = 0;
            $setup->branch_id = $branchId;
            $setup->fee_category_id = $categoryId;
            $setup->amount = $invoice->amount;
            $setup->fee_month = 'One Time';
            $setup->status = 'Active';

            $snapshotCategory = new FeeCategory();
            $snapshotCategory->id = $categoryId ?? 0;
            $snapshotCategory->name = $categoryName;
            $snapshotCategory->exists = true;
            $setup->setRelation('category', $snapshotCategory);

            $snapshotBranch = new Branch();
            $snapshotBranch->id = $branchId ?? 0;
            $snapshotBranch->branch_name = $branchName;
            $snapshotBranch->exists = true;
            $setup->setRelation('branch', $snapshotBranch);
        });
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function activePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class)->where('status', 'Active');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adjustmentLogs(): HasMany
    {
        return $this->hasMany(FeeAdjustmentLog::class);
    }

    public function isAdmissionInvoice(): bool
    {
        return !is_null($this->admission_fee_structure_id);
    }

    /**
     * Determine whether the invoice number follows the canonical Admission Invoice prefix (`INV-ADM-`).
     */
    public function isAdmissionInvoiceNumber(): bool
    {
        return !empty($this->invoice_no)
            && str_starts_with(strtoupper((string) $this->invoice_no), self::ADMISSION_INVOICE_PREFIX);
    }

    /**
     * Extract canonical calendar `year` (4 digits) and `month` (01-12) from legacy `INV-YYYYMM-`
     * or Admission `INV-ADM-S{S}-YYYYMM-{ID}[-Vn]` invoice numbers.
     *
     * @return array{year: string, month: string}|null
     */
    public static function extractYearMonthFromInvoiceNo(?string $invoiceNo): ?array
    {
        if (empty($invoiceNo)) {
            return null;
        }

        if (preg_match(self::ANY_YEAR_MONTH_INVOICE_PATTERN, $invoiceNo, $matches)) {
            return [
                'year' => $matches[1],
                'month' => $matches[2],
            ];
        }

        return null;
    }

    /**
     * Scope query to multi-head Admission Fee invoices only.
     */
    public function scopeAdmissionInvoices($query)
    {
        return $query->whereNotNull('admission_fee_structure_id');
    }

    /**
     * Get the resolved month name for this invoice.
     */
    public function getMonthNameAttribute(): string
    {
        // Multi-head Admission Invoices are one-time admission obligations regardless of head categories
        // (even if the first sorted head is a first-month tuition fee), preventing historical snapshot triggers.
        if ($this->isAdmissionInvoice() || $this->isAdmissionInvoiceNumber()) {
            return 'One Time';
        }

        $setupMonth = trim($this->feeSetup->fee_month ?? '');
        $validMonths = [
            'january', 'february', 'march', 'april', 'may', 'june',
            'july', 'august', 'september', 'october', 'november', 'december'
        ];

        // 1. If fee_month is already an explicit month name
        if ($setupMonth && in_array(strtolower($setupMonth), $validMonths)) {
            return ucfirst(strtolower($setupMonth));
        }

        // 2. Extract from invoice_no if formatted like INV-YYYYMM-XXXX
        if ($parsedPeriod = self::extractYearMonthFromInvoiceNo($this->invoice_no)) {
            $monthNum = (int) $parsedPeriod['month'];
            return date('F', mktime(0, 0, 0, $monthNum, 10));
        }

        // 3. If fee_month is explicitly One Time / Yearly (and not a monthly fee category)
        $categoryName = strtolower($this->feeSetup->category->name ?? '');
        if ($setupMonth && in_array(strtolower($setupMonth), ['one time', 'one_time', 'yearly']) && strpos($categoryName, 'month') === false) {
            return 'One Time';
        }

        // 4. Extract from due_date
        if ($this->due_date) {
            return date('F', strtotime($this->due_date));
        }

        // 5. Extract from created_at
        if ($this->created_at) {
            return date('F', strtotime($this->created_at));
        }

        // 6. Fallback
        if ($setupMonth && !in_array(strtolower($setupMonth), ['monthly', ''])) {
            return ucfirst($setupMonth);
        }

        return 'One Time';
    }

    /**
     * Resolve canonical billing period in 'YYYY-MM' format (e.g. '2026-04').
     */
    public function getBillingPeriodAttribute(): string
    {
        // 1. Extract from invoice_no (supports INV-YYYYMM-... and INV-ADM-S{S}-YYYYMM-...)
        if ($parsedPeriod = self::extractYearMonthFromInvoiceNo($this->invoice_no)) {
            return $parsedPeriod['year'] . '-' . $parsedPeriod['month'];
        }

        // 2. Extract from explicit feeSetup month name if present
        $setupMonth = trim($this->feeSetup->fee_month ?? '');
        $monthNum = Student::resolveMonthNumber($setupMonth);
        $year = $this->due_date ? date('Y', strtotime($this->due_date)) : ($this->created_at ? date('Y', strtotime($this->created_at)) : date('Y'));
        if ($monthNum) {
            return sprintf('%04d-%02d', (int)$year, (int)$monthNum);
        }

        // 3. Extract from due_date
        if ($this->due_date) {
            return date('Y-m', strtotime($this->due_date));
        }

        // 4. Fallback to created_at
        if ($this->created_at) {
            return date('Y-m', strtotime($this->created_at));
        }

        return date('Y-m');
    }

    public function isCancelled(): bool
    {
        return ($this->status ?? '') === 'Cancelled';
    }

    /**
     * Determine whether this invoice is active with zero payable balance (100% waived) and zero actual payment.
     */
    public function isZeroPayableWaived(): bool
    {
        if (
            !array_key_exists('status', $this->attributes)
            || !array_key_exists('net_amount', $this->attributes)
            || $this->isCancelled()
        ) {
            return false;
        }

        $net = bcadd((string) ($this->net_amount ?? '0'), '0.00', 2);
        $paid = bcadd((string) ($this->paid_amount ?? '0'), '0.00', 2);
        $due = bcadd((string) ($this->due_amount ?? '0'), '0.00', 2);

        return bccomp($net, '0.00', 2) === 0
            && bccomp($paid, '0.00', 2) === 0
            && bccomp($due, '0.00', 2) === 0;
    }

    /**
     * Authoritative display status: distinguishes 100%-waived zero-payable invoices from unpaid/paid.
     */
    public function getDisplayStatusAttribute(): string
    {
        if ($this->isZeroPayableWaived()) {
            return 'Waived';
        }

        return (string) ($this->status ?? 'Unpaid');
    }

    public function canBeRestored(): bool
    {
        return $this->isCancelled();
    }

    public function scopeActiveInvoices($query)
    {
        return $query->where('status', '!=', 'Cancelled');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'Cancelled');
    }
}