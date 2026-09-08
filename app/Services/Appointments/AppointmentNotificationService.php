<?php

namespace App\Services\Appointments;

use App\Models\Admin;
use App\Models\Appointment;
use App\Models\AppointmentNotification;
use App\Models\Physician;
use App\Models\PortalUser;

class AppointmentNotificationService
{
    public function notifyAppointmentCreated(Appointment $appointment): void
    {
        $this->notifyPortalUser($appointment, 'appointment_submitted', 'Appointment Submitted',
            "Your appointment {$appointment->appointment_number} has been submitted and is pending confirmation.");

        $this->notifyPhysician($appointment, 'new_appointment', 'New Appointment Request',
            "New appointment request from {$appointment->portalUser->name} on {$appointment->appointment_date->format('M j, Y')} at {$appointment->formattedTimeRange()}.");

        $this->notifyAdmins($appointment, 'new_appointment', 'New Appointment',
            "New appointment {$appointment->appointment_number} pending review.");
    }

    public function notifyAppointmentConfirmed(Appointment $appointment): void
    {
        $this->notifyPortalUser($appointment, 'appointment_confirmed', 'Appointment Confirmed',
            "Your appointment {$appointment->appointment_number} has been confirmed.");
    }

    public function notifyAppointmentRejected(Appointment $appointment): void
    {
        $this->notifyPortalUser($appointment, 'appointment_rejected', 'Appointment Rejected',
            "Your appointment {$appointment->appointment_number} was rejected.");
    }

    public function notifyAppointmentCancelled(Appointment $appointment): void
    {
        $this->notifyPortalUser($appointment, 'appointment_cancelled', 'Appointment Cancelled',
            "Your appointment {$appointment->appointment_number} has been cancelled.");

        $this->notifyPhysician($appointment, 'appointment_cancelled', 'Appointment Cancelled',
            "Appointment {$appointment->appointment_number} has been cancelled.");
    }

    public function notifyAppointmentRescheduled(Appointment $appointment): void
    {
        $this->notifyPortalUser($appointment, 'appointment_rescheduled', 'Appointment Rescheduled',
            "Your appointment {$appointment->appointment_number} has been rescheduled to {$appointment->appointment_date->format('M j, Y')} at {$appointment->formattedTimeRange()}.");

        $this->notifyPhysician($appointment, 'appointment_rescheduled', 'Appointment Rescheduled',
            "Appointment {$appointment->appointment_number} has been rescheduled.");
    }

    public function notifyAppointmentCompleted(Appointment $appointment): void
    {
        $this->notifyPortalUser($appointment, 'appointment_completed', 'Consultation Completed',
            "Your consultation {$appointment->appointment_number} has been marked as completed.");
    }

    public function notifyReminder(Appointment $appointment, int $hoursBefore): void
    {
        $this->notifyPortalUser($appointment, 'appointment_reminder', 'Appointment Reminder',
            "Reminder: You have a consultation in {$hoursBefore} hour(s) on {$appointment->appointment_date->format('M j, Y')} at {$appointment->formattedTimeRange()} with {$appointment->physician->displayName()}.");

        $this->notifyPhysician($appointment, 'appointment_reminder', 'Upcoming Consultation',
            "Reminder: Consultation with {$appointment->portalUser->name} in {$hoursBefore} hour(s).");
    }

    public function getUnreadCount(string $type, int $id): int
    {
        return AppointmentNotification::query()
            ->where('notifiable_type', $type)
            ->where('notifiable_id', $id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, AppointmentNotification>
     */
    public function getRecent(string $type, int $id, int $limit = 20)
    {
        return AppointmentNotification::query()
            ->where('notifiable_type', $type)
            ->where('notifiable_id', $id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    private function notifyPortalUser(Appointment $appointment, string $type, string $title, string $message): void
    {
        AppointmentNotification::query()->create([
            'notifiable_type' => 'portal_user',
            'notifiable_id' => $appointment->portal_user_id,
            'appointment_id' => $appointment->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => ['appointment_number' => $appointment->appointment_number],
        ]);
    }

    private function notifyPhysician(Appointment $appointment, string $type, string $title, string $message): void
    {
        AppointmentNotification::query()->create([
            'notifiable_type' => 'physician',
            'notifiable_id' => $appointment->physician_id,
            'appointment_id' => $appointment->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => ['appointment_number' => $appointment->appointment_number],
        ]);
    }

    private function notifyAdmins(Appointment $appointment, string $type, string $title, string $message): void
    {
        Admin::query()->pluck('id')->each(function ($adminId) use ($appointment, $type, $title, $message) {
            AppointmentNotification::query()->create([
                'notifiable_type' => 'admin',
                'notifiable_id' => $adminId,
                'appointment_id' => $appointment->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => ['appointment_number' => $appointment->appointment_number],
            ]);
        });
    }
}
