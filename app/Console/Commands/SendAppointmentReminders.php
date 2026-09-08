<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\Appointments\AppointmentNotificationService;
use App\Services\Appointments\AppointmentSettingsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Send configurable appointment reminders to patients and physicians';

    public function handle(AppointmentSettingsService $settings, AppointmentNotificationService $notifications): int
    {
        $hours = $settings->get('reminder_hours', [24, 1]);
        if (! is_array($hours)) {
            $hours = [24, 1];
        }

        $count = 0;
        foreach ($hours as $h) {
            $targetStart = now()->addHours($h)->startOfHour();
            $targetEnd = $targetStart->copy()->endOfHour();

            $appointments = Appointment::query()
                ->with(['physician', 'portalUser'])
                ->whereIn('status', [AppointmentStatus::Pending, AppointmentStatus::Confirmed])
                ->whereDate('appointment_date', $targetStart->toDateString())
                ->get()
                ->filter(function (Appointment $apt) use ($targetStart, $targetEnd) {
                    $dt = Carbon::parse($apt->appointment_date->format('Y-m-d').' '.$apt->start_time);

                    return $dt->between($targetStart, $targetEnd);
                });

            foreach ($appointments as $appointment) {
                $notifications->notifyReminder($appointment, (int) $h);
                $count++;
            }
        }

        $this->info("Sent {$count} reminder(s).");

        return self::SUCCESS;
    }
}
