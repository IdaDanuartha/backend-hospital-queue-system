<?php

namespace Database\Factories;

use App\Models\Poly;
use Illuminate\Database\Eloquent\Factories\Factory;

class PolyFactory extends Factory
{
    protected $model = Poly::class;

    public function definition(): array
    {
        return [
            'code' => 'POLI-' . $this->faker->unique()->numberBetween(100, 999),
            'name' => 'Poliklinik ' . $this->faker->randomElement(['Umum', 'Gigi', 'Mata', 'THT', 'Kulit', 'Anak']),
            'location' => 'Gedung ' . $this->faker->randomElement(['A', 'B', 'C']) . ' Lantai ' . $this->faker->numberBetween(1, 3),
            'is_active' => true,
        ];
    }
}
