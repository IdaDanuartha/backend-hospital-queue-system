<?php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Models\Poly;
use App\Models\PolyServiceHour;
use Illuminate\Database\Eloquent\Factories\Factory;

class PolyServiceHourFactory extends Factory
{
    protected $model = PolyServiceHour::class;

    public function definition(): array
    {
        return [
            'poly_id' => Poly::factory(),
            'day_of_week' => $this->faker->randomElement(DayOfWeek::cases()),
            'open_time' => $this->faker->time('H:i:s'),
            'close_time' => $this->faker->time('H:i:s'),
            'is_active' => true,
        ];
    }
}
