<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class VendorAuditLog extends Model
{
    use HasFactory;

    protected $table = 'vendor_audit_logs';

    protected $fillable = [
        'vendor_id',
        'user_id',
        'operator_name',
        'action',
        'entity_type',
        'entity_id',
        'amount',
        'reason',
        'previous_data',
        'new_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'previous_data' => 'array',
        'new_data' => 'array',
    ];

    /**
     * The vendor related to this audit log.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(ExpenseVendor::class, 'vendor_id');
    }

    /**
     * The operator who performed this action.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Record an audit log entry atomically.
     */
    public static function record(
        string $action,
        string $entityType,
        int $entityId,
        ?int $vendorId = null,
        ?float $amount = null,
        ?string $reason = null,
        ?array $previousData = null,
        ?array $newData = null,
        ?User $operator = null
    ): self {
        $operator = $operator ?? Auth::user();

        return static::create([
            'vendor_id' => $vendorId,
            'user_id' => $operator?->id,
            'operator_name' => $operator?->name,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'amount' => $amount,
            'reason' => $reason,
            'previous_data' => $previousData,
            'new_data' => $newData,
        ]);
    }
}
