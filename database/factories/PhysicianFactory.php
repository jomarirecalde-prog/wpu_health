<?php

namespace Database\Factories;

use App\Models\Physician;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<Physician> */
class PhysicianFactory extends Factory
{
    protected $model = Physician::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'professional_title' => 'Dr.',
            'specialization' => 'General Medicine',
            'consultation_duration' => 30,
            'max_daily_appointments' => 20,
            'status' => 'active',
        ];
    }
}
