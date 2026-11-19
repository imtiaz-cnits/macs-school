<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Versioned Admission Fee Structure header scoped by Branch, Class, and Academic Session.
 */
class AdmissionFeeStructure extends Model
{
    use HasFactory;

    protected $table = 'admission_fee_structures';

    /**
     * Note: `active_version` is a MySQL stored generated column and must never be fillable.
     */
    protected $fillable = [
        'branch_id',
        'class_id',
        'session_year_id',
        'structure_name',
        'version',
        'status',
        'total_amount',
        'includes_monthly_tuition',
        'tuition_month_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'class_id' => 'integer',
        'session_year_id' => 'integer',
        'version' => 'integer',
        'total_amount' => 'decimal:2',
        'includes_monthly_tuition' => 'boolean',
        'tuition_month_number' => 'integer',
        'created_by' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function sessionYear(): BelongsTo
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AdmissionFeeStructureItem::class, 'admission_fee_structure_id')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(FeeInvoice::class, 'admission_fee_structure_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeForScope($query, int $branchId, int $classId, ?int $sessionYearId = null)
    {
        $query->where('branch_id', $branchId)
            ->where('class_id', $classId);

        if ($sessionYearId === null) {
            return $query->whereNull('session_year_id');
        }

        return $query->where('session_year_id', $sessionYearId);
    }
}
