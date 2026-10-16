<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $table = 'expense_categories';

    protected $fillable = [
        'name',
        'description',
        'status',
        'user_id',
    ];

    /**
     * The expenses recorded under this category.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_category_id');
    }

    /**
     * The user who created this category.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope query to only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
}
