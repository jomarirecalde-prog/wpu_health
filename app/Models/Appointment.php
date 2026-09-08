<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_number',
        'portal_user_id',
        'patient_record_id',
        'physician_id',
        'appointment_date',
        'start_time',
        'end_time',
        'consultation_type',
        'reason',
        'notes',
        'status',
        'cancellation_reason',
        'created_by',
        'confirmed_by',
        'completed_by',
        'cancelled_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'cancelled_at' => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class);
    }

    public function physician(): BelongsTo
    {
        return $this->belongsTo(Physician::class);
    }

    public function patientRecord(): BelongsTo
    {
        return $this->belongsTo(PatientRecord::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(AppointmentHistory::class)->orderByDesc('changed_at');
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function statusColor(): string
    {
        return $this->status->color();
    }

    public function formattedTimeRange(): string
    {
        return $this->formatTime($this->start_time).' – '.$this->formatTime($this->end_time);
    }

    private function formatTime(string $time): string
    {
        return date('g:i A', strtotime($time));
    }
}
