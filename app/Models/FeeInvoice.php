<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_no', 
        'student_id', 
        'fee_setup_id', 
        'amount', 
        'discount', 
        'net_amount', 
        'paid_amount', 
        'due_amount', 
        'status', 
        'due_date',
        'user_id' 
    ];

    public function student() { 
        return $this->belongsTo(Student::class); 
    }
    
    public function feeSetup() { 
        return $this->belongsTo(FeeSetup::class); 
    }
    
    public function payments() { 
        return $this->hasMany(FeePayment::class); 
    }

    
    public function user() { 
        return $this->belongsTo(User::class); 
    }

    public function adjustmentLogs() {
        return $this->hasMany(FeeAdjustmentLog::class);
    }

    /**
     * Get the resolved month name for this invoice.
     */
    public function getMonthNameAttribute(): string
    {
        $setupMonth = trim($this->feeSetup->fee_month ?? '');
        $validMonths = [
            'january', 'february', 'march', 'april', 'may', 'june',
            'july', 'august', 'september', 'october', 'november', 'december'
        ];

        // 1. If fee_month is already an explicit month name
        if ($setupMonth && in_array(strtolower($setupMonth), $validMonths)) {
            return ucfirst(strtolower($setupMonth));
        }

        // 2. Extract from invoice_no if formatted like INV-YYYYMM-XXXX
        if ($this->invoice_no && preg_match('/INV-\d{4}(0[1-9]|1[0-2])-/i', $this->invoice_no, $matches)) {
            $monthNum = (int)$matches[1];
            return date('F', mktime(0, 0, 0, $monthNum, 10));
        }

        // 3. If fee_month is explicitly One Time / Yearly (and not a monthly fee category)
        $categoryName = strtolower($this->feeSetup->category->name ?? '');
        if ($setupMonth && in_array(strtolower($setupMonth), ['one time', 'one_time', 'yearly']) && strpos($categoryName, 'month') === false) {
            return 'One Time';
        }

        // 4. Extract from due_date
        if ($this->due_date) {
            return date('F', strtotime($this->due_date));
        }

        // 5. Extract from created_at
        if ($this->created_at) {
            return date('F', strtotime($this->created_at));
        }

        // 6. Fallback
        if ($setupMonth && !in_array(strtolower($setupMonth), ['monthly', ''])) {
            return ucfirst($setupMonth);
        }

        return 'One Time';
    }

    /**
     * Resolve canonical billing period in 'YYYY-MM' format (e.g. '2026-04').
     */
    public function getBillingPeriodAttribute(): string
    {
        // 1. Extract from invoice_no (e.g. INV-202604-0001 -> 2026-04)
        if ($this->invoice_no && preg_match('/INV-(\d{4})(0[1-9]|1[0-2])-/i', $this->invoice_no, $matches)) {
            return $matches[1] . '-' . $matches[2];
        }

        // 2. Extract from explicit feeSetup month name if present
        $setupMonth = trim($this->feeSetup->fee_month ?? '');
        $monthNum = Student::resolveMonthNumber($setupMonth);
        $year = $this->due_date ? date('Y', strtotime($this->due_date)) : ($this->created_at ? date('Y', strtotime($this->created_at)) : date('Y'));
        if ($monthNum) {
            return sprintf('%04d-%02d', (int)$year, (int)$monthNum);
        }

        // 3. Extract from due_date
        if ($this->due_date) {
            return date('Y-m', strtotime($this->due_date));
        }

        // 4. Fallback to created_at
        if ($this->created_at) {
            return date('Y-m', strtotime($this->created_at));
        }

        return date('Y-m');
    }

    public function isCancelled(): bool
    {
        return ($this->status ?? '') === 'Cancelled';
    }

    public function canBeRestored(): bool
    {
        return $this->isCancelled();
    }

    public function scopeActiveInvoices($query)
    {
        return $query->where('status', '!=', 'Cancelled');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'Cancelled');
    }
}