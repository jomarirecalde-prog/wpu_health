<?php

namespace Database\Factories;

use App\Models\PortalUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PortalUser> */
class PortalUserFactory extends Factory
{
    protected $model = PortalUser::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'contact_number' => fake()->phoneNumber(),
            'status' => 'active',
        ];
    }
}
