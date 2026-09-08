<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentNumberService
{
    public function generate(Carbon $date): string
    {
        $prefix = config('appointments.appointment_number_prefix', 'WPU');
        $datePart = $date->format('Ymd');

        $lastNumber = Appointment::query()
            ->where('appointment_number', 'like', "{$prefix}-{$datePart}-%")
            ->orderByDesc('appointment_number')
            ->value('appointment_number');

        $sequence = 1;
        if ($lastNumber !== null && preg_match('/-(\d+)$/', $lastNumber, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $datePart, $sequence);
    }
}
