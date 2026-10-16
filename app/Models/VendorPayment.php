<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class VendorPayment extends Model
{
    use HasFactory;

    protected $table = 'vendor_payments';

    protected $fillable = [
        'payment_no',
        'vendor_id',
        'branch_id',
        'expense_id',
        'payment_date',
        'payment_method',
        'amount',
        'reference_no',
        'receipt_file',
        'note',
        'created_from',
        'user_id',
    ];

    protected $casts = [
        'payment_date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    /**
     * The vendor/payee who received this payment.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(ExpenseVendor::class, 'vendor_id');
    }

    /**
     * The branch associated with this payment (optional).
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * The expense voucher if recorded directly during voucher entry.
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    /**
     * The user / operator who recorded this payment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Allocations of this payment to specific bills or opening balances.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(VendorPaymentAllocation::class, 'vendor_payment_id');
    }

    /**
     * Safely generate a unique payment number under high concurrency.
     * Format: VPAY-YYYYMMDD-XXXXXX (e.g. VPAY-20261016-8F3A2B)
     */
    public static function generatePaymentNo(int $maxAttempts = 10): string
    {
        $datePart = date('Ymd');

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $entropy = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $paymentNo = "VPAY-{$datePart}-{$entropy}";

            if (!static::where('payment_no', $paymentNo)->exists()) {
                return $paymentNo;
            }
        }

        throw new \RuntimeException("Failed to generate a unique payment number after {$maxAttempts} attempts.");
    }

    /**
     * Determine if a private receipt file exists for this payment.
     */
    public function hasReceipt(): bool
    {
        return !empty($this->receipt_file) && Storage::disk('local')->exists($this->receipt_file);
    }
}
