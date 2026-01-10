<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'position' => $this->faker->randomElement(['Administrator', 'Manager', 'Supervisor']),
            'department' => $this->faker->randomElement(['IT', 'HR', 'Operations', 'Finance']),
        ];
    }
}
