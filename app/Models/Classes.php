<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Classes extends Model
{
    use HasFactory;

    protected $table = 'classes'; 
    protected $fillable = ['class_name', 'user_id'];

    protected static function booted()
    {
        static::addGlobalScope('custom_order', function ($builder) {
            $builder->orderByRaw("CASE LOWER(class_name) 
                WHEN 'play' THEN 1 
                WHEN 'nursery' THEN 2 
                WHEN 'one' THEN 3 
                WHEN 'two' THEN 4 
                WHEN 'three' THEN 5 
                WHEN 'four' THEN 6 
                WHEN 'five' THEN 7 
                WHEN 'six' THEN 8 
                WHEN 'seven' THEN 9 
                WHEN 'eight' THEN 10 
                WHEN 'nine' THEN 11 
                WHEN 'ten' THEN 12 
                ELSE 999 
            END");
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'class_id');
    }
}