<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsSchedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'filter_criteria'    => 'array',
        'recipient_snapshot' => 'array',
        'scheduled_at'       => 'datetime',
        'dispatched_at'      => 'datetime',
        'cancelled_at'       => 'datetime',
        'estimated_cost'     => 'decimal:2',
        'actual_cost'        => 'decimal:2',
        'total_recipients'   => 'integer',
        'total_segments'     => 'integer',
        'sent_count'         => 'integer',
        'failed_count'       => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledByUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeEdited(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeCancelled(): bool
    {
        return $this->status === 'pending';
    }
}
