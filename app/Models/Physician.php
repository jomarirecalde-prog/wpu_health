<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Physician extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'staff_signature_id',
        'name',
        'email',
        'password',
        'professional_title',
        'specialization',
        'license_no',
        'department_id',
        'consultation_location',
        'consultation_duration',
        'max_daily_appointments',
        'consultation_types',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'consultation_types' => 'array',
            'password' => 'hashed',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function displayName(): string
    {
        $title = $this->professional_title ? $this->professional_title.' ' : '';

        return $title.$this->name;
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(PhysicianSchedule::class);
    }

    public function scheduleExceptions(): HasMany
    {
        return $this->hasMany(PhysicianScheduleException::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppointmentNotification::class, 'notifiable_id')
            ->where('notifiable_type', 'physician');
    }
}
