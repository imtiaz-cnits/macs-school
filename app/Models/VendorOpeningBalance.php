<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VendorOpeningBalance extends Model
{
    use HasFactory;

    protected $table = 'vendor_opening_balances';

    protected $fillable = [
        'vendor_id',
        'branch_id',
        'opening_balance',
        'paid_amount',
        'effective_date',
        'reference_no',
        'reference_date',
        'status',
        'note',
        'user_id',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'effective_date' => 'date:Y-m-d',
        'reference_date' => 'date:Y-m-d',
    ];

    /**
     * The vendor this opening balance belongs to.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(ExpenseVendor::class, 'vendor_id');
    }

    /**
     * The branch associated with this balance (optional).
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * The user / operator who recorded this opening balance.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Payment allocations made against this opening balance.
     */
    public function paymentAllocations(): MorphMany
    {
        return $this->morphMany(VendorPaymentAllocation::class, 'allocatable');
    }

    /**
     * Remaining due amount on this opening balance.
     */
    public function getDueAmountAttribute(): float
    {
        return max(0.0, round((float) $this->opening_balance - (float) $this->paid_amount, 2));
    }

    /**
     * Recalculate status based on paid amount.
     */
    public function updateStatus(): void
    {
        $due = $this->due_amount;
        if ($due <= 0.0) {
            $this->status = 'Paid';
        } elseif ((float) $this->paid_amount > 0.0) {
            $this->status = 'Partially Paid';
        } else {
            $this->status = 'Unpaid';
        }
        $this->save();
    }
}
