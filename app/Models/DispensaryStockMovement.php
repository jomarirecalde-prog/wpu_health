<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispensaryStockMovement extends Model
{
    public $timestamps = false;

    protected $table = 'dispensary_stock_movements';

    /**
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_delta' => 'decimal:2',
            'quantity_after' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(DispensaryItem::class, 'item_id');
    }
}
