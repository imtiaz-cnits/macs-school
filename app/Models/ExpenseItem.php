<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseItem extends Model
{
    use HasFactory;

    protected $table = 'expense_items';

    protected $fillable = [
        'expense_id',
        'expense_category_id',
        'item_name',
        'quantity',
        'unit_rate',
        'line_total',
        'note',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    /**
     * The expense voucher this line item belongs to.
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    /**
     * The specific expense category this line item belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
