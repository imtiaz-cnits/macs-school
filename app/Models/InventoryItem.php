<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $table = 'inventory_items';

    protected $fillable = [
        'name',
        'type',
        'class_id',
        'description',
        'current_quantity',
        'unit'
    ];

    /**
     * Get the class associated with this textbook (if type is book)
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /**
     * Get the transaction logs for this inventory item
     */
    public function logs(): HasMany
    {
        return $this->hasMany(InventoryLog::class, 'inventory_item_id')->orderBy('date', 'desc')->orderBy('created_at', 'desc');
    }
}
