<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Physician;
use App\Models\PortalUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Appointment> */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $date = now()->addDays(3);

        return [
            'appointment_number' => 'WPU-'.$date->format('Ymd').'-0001',
            'portal_user_id' => PortalUser::factory(),
            'physician_id' => Physician::factory(),
            'appointment_date' => $date->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'consultation_type' => 'general',
            'reason' => fake()->sentence(),
            'status' => AppointmentStatus::Pending,
        ];
    }
}
