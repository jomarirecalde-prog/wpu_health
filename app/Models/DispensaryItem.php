<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispensaryItem extends Model
{
    protected $table = 'dispensary_items';

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
            'quantity_on_hand' => 'decimal:2',
            'reorder_threshold' => 'decimal:2',
            'expiration_date' => 'date',
        ];
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(DispensaryStockMovement::class, 'item_id');
    }
}
