<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PortalUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'contact_number',
        'patient_type_id',
        'department_id',
        'employee_student_id',
        'date_of_birth',
        'address',
        'status',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function patientRecord(): BelongsTo
    {
        return $this->belongsTo(PatientRecord::class, 'employee_student_id', 'student_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppointmentNotification::class, 'notifiable_id')
            ->where('notifiable_type', 'portal_user');
    }
}
