<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;
    
    // সব ফিল্ড মাস অ্যাসাইনমেন্ট করার অনুমতি (এটি আপনার 500 এরর সমাধান করবে)
    protected $guarded = []; 

    /**
     * Relationships
     */

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // class_id এর মাধ্যমে রিলেশন (আপনার ডাটাবেস অনুযায়ী)
    public function schoolClass()
    {
        return $this->belongsTo(Classes::class, 'class_id'); 
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function sessionYear()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function academicHistories() 
    { 
        return $this->hasMany(StudentAcademicHistory::class); 
    }

    public function customFees()
    {
        return $this->hasMany(StudentCustomFee::class);
    }

    public function invoices()
    {
        return $this->hasMany(FeeInvoice::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(StudentStatusLog::class)->orderBy('id', 'desc');
    }

    /**
     * Photo Accessor: ছবি না থাকলে ডিফল্ট ছবি দেখাবে
     */
   public function getPhotoAttribute($value)
        {
            if (!$value) {
                // .jpg এর বদলে .png দিন কারণ আপনার ফোল্ডারে ফাইলগুলো .png ফরমেটে আছে
                return $this->gender === 'Male' 
                    ? 'student_photos/boy.png' 
                    : 'student_photos/girl.png';
            }
            return $value;
        }

    /**
     * Query Scopes for Student Status
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'Inactive');
    }

    /**
     * Check if student is active
     */
    public function isActive(): bool
    {
        return ($this->status ?? 'Active') === 'Active';
    }

    /**
     * Helper to resolve month number ('01' to '12') from various representations.
     */
    public static function resolveMonthNumber(int|string $month): ?string
    {
        if (is_numeric($month)) {
            $num = (int) $month;
            if ($num >= 1 && $num <= 12) {
                return str_pad((string) $num, 2, '0', STR_PAD_LEFT);
            }
        }

        $monthsMap = [
            'january' => '01', 'february' => '02', 'march' => '03',
            'april' => '04', 'may' => '05', 'june' => '06',
            'july' => '07', 'august' => '08', 'september' => '09',
            'october' => '10', 'november' => '11', 'december' => '12',
            'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
            'may' => '05', 'jun' => '06', 'jul' => '07', 'aug' => '08',
            'sep' => '09', 'oct' => '10', 'nov' => '11', 'dec' => '12'
        ];

        $key = strtolower(trim((string) $month));
        return $monthsMap[$key] ?? null;
    }

    /**
     * Check if the student is eligible to be billed for a given year and month.
     *
     * Rules:
     * 1. Active students are eligible.
     * 2. Inactive students are eligible ONLY for historical billing months on or before their discontinued_month.
     * 3. Inactive students are strictly excluded for any billing period after their discontinued_month.
     */
    public function isEligibleForBilling(int $year, int|string $month): bool
    {
        $monthNum = self::resolveMonthNumber($month);
        if (!$monthNum) {
            return false;
        }

        $targetPeriod = sprintf('%04d-%02d', $year, $monthNum);

        // If currently Active
        if (($this->status ?? 'Active') === 'Active') {
            return true;
        }

        // If Inactive, check if discontinued_month is on or after the target period
        if (!empty($this->discontinued_month)) {
            $discontinued = trim($this->discontinued_month);
            if (preg_match('/^\d{4}-\d{2}$/', $discontinued)) {
                return $targetPeriod <= $discontinued;
            }
            if (preg_match('/^(\d{4}-\d{2})-\d{2}$/', $discontinued, $m)) {
                return $targetPeriod <= $m[1];
            }
        }

        return false;
    }

    /**
     * Scope query to students eligible for billing in a given year and month.
     */
    public function scopeEligibleForBilling($query, int $year, int|string $month)
    {
        $monthNum = self::resolveMonthNumber($month);
        if (!$monthNum) {
            return $query->whereRaw('1 = 0');
        }

        $targetPeriod = sprintf('%04d-%02d', $year, $monthNum);

        return $query->where(function ($q) use ($targetPeriod) {
            $q->where('status', 'Active')
              ->orWhere(function ($sub) use ($targetPeriod) {
                  $sub->where('status', 'Inactive')
                      ->whereNotNull('discontinued_month')
                      ->where('discontinued_month', '!=', '')
                      ->where('discontinued_month', '>=', $targetPeriod);
              });
        });
    }
}