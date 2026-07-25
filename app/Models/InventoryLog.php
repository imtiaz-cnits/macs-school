<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    use HasFactory;

    protected $table = 'inventory_logs';

    protected $fillable = [
        'inventory_item_id',
        'user_id',
        'type',
        'quantity',
        'date',
        'remarks'
    ];

    /**
     * Get the item associated with this transaction log
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Get the operator user who logged this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
