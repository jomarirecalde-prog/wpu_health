<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhysicianScheduleException extends Model
{
    protected $fillable = [
        'physician_id',
        'date',
        'start_time',
        'end_time',
        'type',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function physician(): BelongsTo
    {
        return $this->belongsTo(Physician::class);
    }
}
