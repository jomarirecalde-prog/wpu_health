<?php

namespace Database\Seeders;

use App\Models\Physician;
use App\Models\PhysicianSchedule;
use App\Models\PortalUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AppointmentModuleSeeder extends Seeder
{
    public function run(): void
    {
        $physician = Physician::query()->firstOrCreate(
            ['email' => 'physician@wpu.edu.ph'],
            [
                'name' => 'Juan Dela Cruz',
                'password' => Hash::make('password123'),
                'professional_title' => 'Dr.',
                'specialization' => 'General Medicine',
                'consultation_duration' => 30,
                'max_daily_appointments' => 20,
                'status' => 'active',
            ]
        );

        if ($physician->schedules()->count() === 0) {
            foreach ([1, 2, 4, 5] as $day) {
                PhysicianSchedule::query()->create([
                    'physician_id' => $physician->id,
                    'day_of_week' => $day,
                    'start_time' => '08:00:00',
                    'end_time' => '12:00:00',
                    'is_break' => false,
                ]);
                PhysicianSchedule::query()->create([
                    'physician_id' => $physician->id,
                    'day_of_week' => $day,
                    'start_time' => '12:00:00',
                    'end_time' => '13:00:00',
                    'is_break' => true,
                ]);
                PhysicianSchedule::query()->create([
                    'physician_id' => $physician->id,
                    'day_of_week' => $day,
                    'start_time' => '13:00:00',
                    'end_time' => '17:00:00',
                    'is_break' => false,
                ]);
            }
        }

        PortalUser::query()->firstOrCreate(
            ['email' => 'patient@wpu.edu.ph'],
            [
                'name' => 'Maria Cruz',
                'password' => Hash::make('password123'),
                'contact_number' => '09171234567',
                'status' => 'active',
            ]
        );
    }
}
