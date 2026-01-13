<?php

namespace Database\Factories;

use App\Models\QueueTicket;
use App\Models\QueueType;
use App\Enums\QueueStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class QueueTicketFactory extends Factory
{
    protected $model = QueueTicket::class;

    public function definition(): array
    {
        return [
            'queue_type_id' => QueueType::factory(),
            'queue_number' => $this->faker->unique()->numberBetween(1, 999),
            'display_number' => 'T-' . $this->faker->unique()->numberBetween(1, 999),
            'patient_name' => $this->faker->name(),
            'status' => QueueStatus::WAITING,
            'service_date' => today(),
            'issued_at' => now(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function waiting(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => QueueStatus::WAITING,
        ]);
    }

    public function called(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => QueueStatus::CALLED,
            'called_at' => now(),
        ]);
    }

    public function serving(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => QueueStatus::SERVING,
            'called_at' => now()->subMinutes(5),
            'service_started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => QueueStatus::DONE,
            'called_at' => now()->subMinutes(10),
            'service_started_at' => now()->subMinutes(5),
            'service_completed_at' => now(),
        ]);
    }
}
