<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseAuditLog extends Model
{
    use HasFactory;

    protected $table = 'expense_audit_logs';

    protected $fillable = [
        'expense_id',
        'user_id',
        'voucher_no',
        'action',
        'amount',
        'expense_date',
        'category_name',
        'payment_method',
        'paid_to',
        'operator_name',
        'reason',
        'previous_data',
        'new_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date:Y-m-d',
        'previous_data' => 'array',
        'new_data' => 'array',
    ];

    /**
     * The expense this log entry relates to (nullable if original expense was deleted).
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    /**
     * The user / operator who performed this action.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Atomically create an audit record capturing snapshot values.
     * Preserves voucher number, operator name, category name, amount, date, and reason
     * permanently, even if the related expense or user record is later deleted.
     */
    public static function record(
        Expense $expense,
        string $action,
        ?User $operator = null,
        ?string $reason = null,
        ?array $previousData = null,
        ?array $newData = null
    ): self {
        $operator = $operator ?? auth()->user();

        return static::create([
            'expense_id'    => $expense->id,
            'user_id'       => $operator?->id,
            'voucher_no'    => $expense->voucher_no,
            'action'        => $action,
            'amount'        => $expense->amount,
            'expense_date'  => $expense->expense_date,
            'category_name' => $expense->category?->name ?? ($previousData['category_name'] ?? null),
            'payment_method'=> $expense->payment_method,
            'paid_to'       => $expense->paid_to,
            'operator_name' => $operator?->name,
            'reason'        => $reason,
            'previous_data' => $previousData,
            'new_data'      => $newData,
        ]);
    }
}
