<?php

namespace Database\Seeders;

use App\Models\Poly;
use App\Models\PolyServiceHour;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PolySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $polys = [
            [
                'code' => 'POLI-UMUM',
                'name' => 'Poli Umum',
                'location' => 'Lantai 1, Ruang A',
                'is_active' => true,
            ],
            [
                'code' => 'POLI-GIGI',
                'name' => 'Poli Gigi',
                'location' => 'Lantai 1, Ruang B',
                'is_active' => true,
            ],
            [
                'code' => 'POLI-ANAK',
                'name' => 'Poli Anak',
                'location' => 'Lantai 2, Ruang A',
                'is_active' => true,
            ],
            [
                'code' => 'POLI-KANDUNGAN',
                'name' => 'Poli Kandungan',
                'location' => 'Lantai 2, Ruang B',
                'is_active' => true,
            ],
            [
                'code' => 'POLI-JANTUNG',
                'name' => 'Poli Jantung',
                'location' => 'Lantai 3, Ruang A',
                'is_active' => true,
            ],
        ];

        foreach ($polys as $poly) {
            $createdPoly = Poly::create($poly);
            
            // Create service hours for each poly (Monday - Saturday)
            for ($day = 1; $day <= 6; $day++) {
                PolyServiceHour::create([
                    'poly_id' => $createdPoly->id,
                    'day_of_week' => $day,
                    'open_time' => '08:00',
                    'close_time' => '16:00',
                    'is_active' => true,
                ]);
            }
        }
    }
}
