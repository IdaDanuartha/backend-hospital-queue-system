<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Poly;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition(): array
    {
        return [
            'poly_id' => Poly::factory(),
            'sip_number' => 'SIP.' . $this->faker->unique()->numerify('###.###.###'),
            'name' => 'Dr. ' . $this->faker->name() . ', ' . $this->faker->randomElement(['Sp.PD', 'Sp.A', 'Sp.OG', 'Sp.JP', 'Sp.M']),
            'specialization' => $this->faker->randomElement([
                'Spesialis Penyakit Dalam',
                'Spesialis Anak',
                'Spesialis Kandungan',
                'Spesialis Jantung',
                'Spesialis Mata',
            ]),
        ];
    }
}
