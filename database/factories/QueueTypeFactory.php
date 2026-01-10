<?php

namespace Database\Factories;

use App\Models\QueueType;
use App\Models\Poly;
use Illuminate\Database\Eloquent\Factories\Factory;

class QueueTypeFactory extends Factory
{
    protected $model = QueueType::class;

    public function definition(): array
    {
        return [
            'poly_id' => Poly::factory(),
            'code_prefix' => 'QT',
            'name' => 'Antrian ' . $this->faker->randomElement(['Umum', 'BPJS', 'Prioritas']),
            'service_unit' => $this->faker->word(),
            'avg_service_minutes' => $this->faker->numberBetween(10, 30),
            'is_active' => true,
        ];
    }
}
