<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VendorPaymentAllocation extends Model
{
    use HasFactory;

    protected $table = 'vendor_payment_allocations';

    protected $fillable = [
        'vendor_payment_id',
        'allocatable_type',
        'allocatable_id',
        'allocated_amount',
        'note',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    /**
     * The payment transaction this allocation belongs to.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(VendorPayment::class, 'vendor_payment_id');
    }

    /**
     * The entity being paid (an Expense voucher bill or VendorOpeningBalance).
     */
    public function allocatable(): MorphTo
    {
        return $this->morphTo();
    }
}
