<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

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
        'profile_photo_path',
        'status',
        'email_verified_at',
        'password_changed_at',
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
            'password_changed_at' => 'datetime',
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

    public function patientType(): BelongsTo
    {
        return $this->belongsTo(PatientType::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));
        }

        return strtoupper(mb_substr($this->name, 0, 2));
    }

    public function profilePhotoUrl(): ?string
    {
        if (! $this->profile_photo_path || ! Storage::disk('public')->exists($this->profile_photo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->profile_photo_path);
    }

    public function hasProfilePhoto(): bool
    {
        return $this->profilePhotoUrl() !== null;
    }
}
