<?php

namespace App\Services\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Physician;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentStatusService
{
    public function __construct(
        private readonly AppointmentBookingService $bookingService,
        private readonly SlotGenerationService $slotService,
        private readonly AppointmentNotificationService $notificationService,
        private readonly AppointmentSettingsService $settings,
    ) {}

    public function confirm(Appointment $appointment, string $actor, string $actorType = 'admin'): Appointment
    {
        return $this->transition($appointment, AppointmentStatus::Confirmed, 'confirmed', $actor, $actorType, function ($apt) use ($actor) {
            $apt->confirmed_by = $actor;
            $this->notificationService->notifyAppointmentConfirmed($apt);
        });
    }

    public function reject(Appointment $appointment, string $actor, string $reason, string $actorType = 'admin'): Appointment
    {
        return $this->transition($appointment, AppointmentStatus::Rejected, 'rejected', $actor, $actorType, function ($apt) use ($reason) {
            $apt->cancellation_reason = $reason;
            $this->notificationService->notifyAppointmentRejected($apt);
        }, $reason);
    }

    public function cancel(Appointment $appointment, string $actor, ?string $reason, string $actorType): Appointment
    {
        $this->assertCancellationAllowed($appointment, $actorType);

        return $this->transition($appointment, AppointmentStatus::Cancelled, 'cancelled', $actor, $actorType, function ($apt) use ($actor, $reason) {
            $apt->cancellation_reason = $reason;
            $apt->cancelled_by = $actor;
            $apt->cancelled_at = now();
            $this->notificationService->notifyAppointmentCancelled($apt);
        }, $reason);
    }

    public function complete(Appointment $appointment, string $actor, string $actorType = 'physician'): Appointment
    {
        return $this->transition($appointment, AppointmentStatus::Completed, 'completed', $actor, $actorType, function ($apt) use ($actor) {
            $apt->completed_by = $actor;
            $this->notificationService->notifyAppointmentCompleted($apt);
        });
    }

    public function markNoShow(Appointment $appointment, string $actor, string $actorType = 'physician'): Appointment
    {
        return $this->transition($appointment, AppointmentStatus::NoShow, 'no_show', $actor, $actorType);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reschedule(Appointment $appointment, array $data, string $actor, string $actorType): Appointment
    {
        if (! $appointment->status->canTransitionTo(AppointmentStatus::Rescheduled)
            && ! $appointment->status->canTransitionTo(AppointmentStatus::Confirmed)) {
            throw ValidationException::withMessages(['status' => 'This appointment cannot be rescheduled.']);
        }

        $physician = Physician::query()->findOrFail($data['physician_id'] ?? $appointment->physician_id);
        $newDate = Carbon::parse($data['appointment_date']);
        $newStartTime = $data['start_time'];
        $reason = $data['reason'] ?? null;

        $this->bookingService->validateSlot($physician, $newDate, $newStartTime);

        return DB::transaction(function () use ($appointment, $physician, $newDate, $newStartTime, $reason, $actor, $actorType) {
            $oldStatus = $appointment->status->value;
            $oldDate = $appointment->appointment_date;
            $oldStartTime = $appointment->start_time;

            $duration = $physician->consultation_duration ?: $this->settings->get('default_consultation_duration', 30);
            $newEndTime = Carbon::parse($newDate->format('Y-m-d').' '.$newStartTime)
                ->addMinutes($duration)
                ->format('H:i:s');

            $conflict = Appointment::query()
                ->where('physician_id', $physician->id)
                ->whereDate('appointment_date', $newDate->format('Y-m-d'))
                ->where('start_time', $newStartTime)
                ->where('id', '!=', $appointment->id)
                ->whereIn('status', AppointmentStatus::blockingStatuses())
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'start_time' => 'This time slot is no longer available.',
                ]);
            }

            $appointment->update([
                'physician_id' => $physician->id,
                'appointment_date' => $newDate->format('Y-m-d'),
                'start_time' => $newStartTime,
                'end_time' => $newEndTime,
                'status' => AppointmentStatus::Pending,
            ]);

            $this->bookingService->recordHistory(
                $appointment,
                'rescheduled',
                $oldStatus,
                AppointmentStatus::Pending->value,
                $actor,
                $actorType,
                $reason,
                Carbon::parse($oldDate),
                $newDate,
                $oldStartTime,
                $newStartTime
            );

            $this->notificationService->notifyAppointmentRescheduled($appointment);

            return $appointment->fresh(['physician', 'portalUser']);
        });
    }

    /**
     * @param  callable(Appointment): void|null  $beforeSave
     */
    private function transition(
        Appointment $appointment,
        AppointmentStatus $newStatus,
        string $action,
        string $actor,
        string $actorType,
        ?callable $beforeSave = null,
        ?string $reason = null,
    ): Appointment {
        $current = $appointment->status;

        if (! $current->canTransitionTo($newStatus) && $current !== $newStatus) {
            throw ValidationException::withMessages([
                'status' => "Cannot change status from {$current->label()} to {$newStatus->label()}.",
            ]);
        }

        $oldStatus = $current->value;

        if ($beforeSave) {
            $beforeSave($appointment);
        }

        $appointment->status = $newStatus;
        $appointment->save();

        $this->bookingService->recordHistory(
            $appointment,
            $action,
            $oldStatus,
            $newStatus->value,
            $actor,
            $actorType,
            $reason
        );

        return $appointment->fresh(['physician', 'portalUser']);
    }

    private function assertCancellationAllowed(Appointment $appointment, string $actorType): void
    {
        if (in_array($actorType, ['admin', 'physician'], true)) {
            return;
        }

        $hours = (int) $this->settings->get('cancellation_hours_before', 2);
        $appointmentDateTime = Carbon::parse(
            $appointment->appointment_date->format('Y-m-d').' '.$appointment->start_time
        );

        if ($appointmentDateTime->lte(now()->addHours($hours))) {
            throw ValidationException::withMessages([
                'cancellation' => "Cancellation is only allowed at least {$hours} hour(s) before the appointment.",
            ]);
        }
    }
}
