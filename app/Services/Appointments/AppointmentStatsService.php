<?php

namespace App\Services\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AppointmentStatsService
{
    private const CACHE_KEY = 'appointment:dashboard:stats';

    private const CACHE_TTL = 60;

    /**
     * @return array<string, int>
     */
    public function getDashboardStats(?Carbon $date = null): array
    {
        $date = $date ?? today();

        return Cache::remember(self::CACHE_KEY.':'.$date->format('Y-m-d'), self::CACHE_TTL, function () use ($date) {
            $base = Appointment::query()->whereDate('appointment_date', $date);

            return [
                'today_total' => (clone $base)->count(),
                'today_pending' => (clone $base)->where('status', AppointmentStatus::Pending)->count(),
                'today_confirmed' => (clone $base)->where('status', AppointmentStatus::Confirmed)->count(),
                'today_completed' => (clone $base)->where('status', AppointmentStatus::Completed)->count(),
                'today_cancelled' => (clone $base)->where('status', AppointmentStatus::Cancelled)->count(),
                'today_no_show' => (clone $base)->where('status', AppointmentStatus::NoShow)->count(),
                'upcoming' => Appointment::query()
                    ->whereDate('appointment_date', '>=', $date)
                    ->whereIn('status', [AppointmentStatus::Pending, AppointmentStatus::Confirmed])
                    ->count(),
            ];
        });
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY.':'.today()->format('Y-m-d'));
    }
}
