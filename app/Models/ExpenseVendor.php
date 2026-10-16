<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseVendor extends Model
{
    use HasFactory;

    protected $table = 'expense_vendors';

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'address',
        'status',
        'user_id',
    ];

    /**
     * The expenses billed by this vendor.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'vendor_id');
    }

    /**
     * The opening balances recorded for this vendor.
     */
    public function openingBalances(): HasMany
    {
        return $this->hasMany(VendorOpeningBalance::class, 'vendor_id');
    }

    /**
     * The payment transactions recorded for this vendor.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class, 'vendor_id');
    }

    /**
     * The user who registered this vendor.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope query to only active vendors.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Calculate total outstanding dues for this vendor (Opening Dues + Unpaid Bills).
     */
    public function getTotalDueAttribute(): float
    {
        $openingDue = (float) $this->openingBalances->sum(function ($ob) {
            return $ob->due_amount;
        });

        $billsDue = (float) $this->expenses->sum(function ($exp) {
            return $exp->due_amount;
        });

        return round($openingDue + $billsDue, 2);
    }

    /**
     * Total amount billed across all vouchers.
     */
    public function getTotalBilledAttribute(): float
    {
        return round((float) $this->expenses->sum('amount'), 2);
    }

    /**
     * Total amount paid across all transactions.
     */
    public function getTotalPaidAttribute(): float
    {
        return round((float) $this->payments->sum('amount'), 2);
    }
}
