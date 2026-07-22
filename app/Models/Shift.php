<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 'shift_name', 'type', 'in_time_start', 
        'in_time_end', 'late_time_end', 'absent_time', 
        'out_time', 'user_id'
    ];

    public function getShiftNameAttribute()
    {
        return $this->name ?? $this->attributes['shift_name'] ?? '';
    }

    public function setShiftNameAttribute($value)
    {
        $this->attributes['shift_name'] = $value;
        $this->attributes['name'] = $value;
    }
}
