<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Immutable head-level financial breakdown, discount snapshot, and running payment balance
 * for an Admission Fee Invoice.
 */
class AdmissionInvoiceHeadAllocation extends Model
{
    use HasFactory;

    protected $table = 'admission_invoice_head_allocations';

    protected $fillable = [
        'fee_invoice_id',
        'student_id',
        'branch_id',
        'admission_fee_structure_item_id',
        'fee_category_id',
        'fee_category_name_snapshot',
        'head_name_snapshot',
        'branch_name_snapshot',
        'amount',
        'discount',
        'net_amount',
        'allocated_paid_amount',
        'due_amount',
        'is_discountable',
        'is_tuition_head',
        'sort_order',
    ];

    protected $casts = [
        'fee_invoice_id' => 'integer',
        'student_id' => 'integer',
        'branch_id' => 'integer',
        'admission_fee_structure_item_id' => 'integer',
        'fee_category_id' => 'integer',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'allocated_paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'is_discountable' => 'boolean',
        'is_tuition_head' => 'boolean',
        'sort_order' => 'integer',
    ];

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

    public function structureItem(): BelongsTo
    {
        return $this->belongsTo(AdmissionFeeStructureItem::class, 'admission_fee_structure_item_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(AdmissionPaymentHeadAllocation::class, 'admission_invoice_head_allocation_id');
    }

    public function activePaymentAllocations(): HasMany
    {
        return $this->hasMany(AdmissionPaymentHeadAllocation::class, 'admission_invoice_head_allocation_id')
            ->where('status', 'Active');
    }
}
