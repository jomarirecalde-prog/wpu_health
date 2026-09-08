<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhysicianSchedule extends Model
{
    protected $fillable = [
        'physician_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_break',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'is_break' => 'boolean',
            'is_available' => 'boolean',
        ];
    }

    public function physician(): BelongsTo
    {
        return $this->belongsTo(Physician::class);
    }
}
