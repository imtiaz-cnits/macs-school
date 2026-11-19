<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Individual Fee Head item belonging to an AdmissionFeeStructure.
 */
class AdmissionFeeStructureItem extends Model
{
    use HasFactory;

    protected $table = 'admission_fee_structure_items';

    protected $fillable = [
        'admission_fee_structure_id',
        'fee_category_id',
        'head_name',
        'amount',
        'is_discountable',
        'is_tuition_head',
        'sort_order',
    ];

    protected $casts = [
        'admission_fee_structure_id' => 'integer',
        'fee_category_id' => 'integer',
        'amount' => 'decimal:2',
        'is_discountable' => 'boolean',
        'is_tuition_head' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(AdmissionFeeStructure::class, 'admission_fee_structure_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function invoiceAllocations(): HasMany
    {
        return $this->hasMany(AdmissionInvoiceHeadAllocation::class, 'admission_fee_structure_item_id');
    }
}
