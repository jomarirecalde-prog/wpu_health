<?php

namespace App\Services\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentHistory;
use App\Models\Physician;
use App\Models\PortalUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentBookingService
{
    public function __construct(
        private readonly SlotGenerationService $slotService,
        private readonly AppointmentNumberService $numberService,
        private readonly AppointmentNotificationService $notificationService,
        private readonly AppointmentSettingsService $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function book(array $data, PortalUser $user, ?string $createdBy = null): Appointment
    {
        if (! $user->isActive()) {
            throw ValidationException::withMessages(['user' => 'Your account is not active.']);
        }

        $physician = Physician::query()->findOrFail($data['physician_id']);
        $date = Carbon::parse($data['appointment_date']);
        $startTime = $data['start_time'];

        $this->validateSlot($physician, $date, $startTime);

        return DB::transaction(function () use ($data, $user, $physician, $date, $startTime, $createdBy) {
            $this->lockPhysicianSlot($physician->id, $date, $startTime);

            $duration = $physician->consultation_duration ?: $this->settings->get('default_consultation_duration', 30);
            $endTime = Carbon::parse($date->format('Y-m-d').' '.$startTime)
                ->addMinutes($duration)
                ->format('H:i:s');

            $existing = Appointment::query()
                ->where('physician_id', $physician->id)
                ->whereDate('appointment_date', $date->format('Y-m-d'))
                ->where('start_time', $startTime)
                ->whereIn('status', AppointmentStatus::blockingStatuses())
                ->lockForUpdate()
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'start_time' => 'This time slot is no longer available. Please choose another.',
                ]);
            }

            $appointment = Appointment::query()->create([
                'appointment_number' => $this->numberService->generate($date),
                'portal_user_id' => $user->id,
                'patient_record_id' => $data['patient_record_id'] ?? null,
                'physician_id' => $physician->id,
                'appointment_date' => $date->format('Y-m-d'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'consultation_type' => $data['consultation_type'] ?? 'general',
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => AppointmentStatus::Pending,
                'created_by' => $createdBy ?? $user->email,
            ]);

            $this->recordHistory($appointment, 'created', null, AppointmentStatus::Pending->value, $createdBy ?? $user->email, 'portal_user');

            $this->notificationService->notifyAppointmentCreated($appointment);

            return $appointment->load(['physician', 'portalUser']);
        });
    }

    public function validateSlot(Physician $physician, Carbon $date, string $startTime): void
    {
        if ($date->isPast() && ! $date->isToday()) {
            throw ValidationException::withMessages(['appointment_date' => 'Cannot book appointments in the past.']);
        }

        if (! $physician->isActive()) {
            throw ValidationException::withMessages(['physician_id' => 'Selected physician is not available.']);
        }

        $available = collect($this->slotService->getAvailableSlots($physician, $date));
        $normalizedStart = substr($startTime, 0, 8);

        if (! $available->contains(fn ($slot) => substr($slot['start'], 0, 8) === $normalizedStart)) {
            throw ValidationException::withMessages([
                'start_time' => 'The selected time slot is not available.',
            ]);
        }
    }

    private function lockPhysicianSlot(int $physicianId, Carbon $date, string $startTime): void
    {
        Appointment::query()
            ->where('physician_id', $physicianId)
            ->whereDate('appointment_date', $date->format('Y-m-d'))
            ->where('start_time', $startTime)
            ->lockForUpdate()
            ->get();
    }

    public function recordHistory(
        Appointment $appointment,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        ?string $changedBy,
        string $changedByType,
        ?string $reason = null,
        ?Carbon $oldDate = null,
        ?Carbon $newDate = null,
        ?string $oldStartTime = null,
        ?string $newStartTime = null,
    ): void {
        AppointmentHistory::query()->create([
            'appointment_id' => $appointment->id,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'old_date' => $oldDate?->format('Y-m-d'),
            'new_date' => $newDate?->format('Y-m-d'),
            'old_start_time' => $oldStartTime,
            'new_start_time' => $newStartTime,
            'reason' => $reason,
            'changed_by' => $changedBy,
            'changed_by_type' => $changedByType,
            'changed_at' => now(),
        ]);
    }
}
