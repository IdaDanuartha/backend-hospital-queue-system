<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\User;
use App\Models\Poly;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'poly_id' => Poly::factory(),
            'code' => 'STF-' . $this->faker->unique()->numberBetween(1000, 9999),
            'is_active' => true,
        ];
    }
}
