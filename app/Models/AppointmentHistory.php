<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentHistory extends Model
{
    public $timestamps = false;

    protected $table = 'appointment_history';

    protected $fillable = [
        'appointment_id',
        'action',
        'old_status',
        'new_status',
        'old_date',
        'new_date',
        'old_start_time',
        'new_start_time',
        'reason',
        'changed_by',
        'changed_by_type',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'old_date' => 'date',
            'new_date' => 'date',
            'changed_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
