<?php

namespace Database\Seeders;

use App\Models\Poly;
use App\Models\QueueType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QueueTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $polys = Poly::all();

        $queueTypes = [
            // Poli-based queues
            [
                'poly_id' => $polys->where('code', 'POLI-UMUM')->first()->id,
                'name' => 'Poli Umum - BPJS',
                'code_prefix' => 'A',
                'service_unit' => 'Rawat Jalan',
                'avg_service_minutes' => 15,
                'is_active' => true,
            ],
            [
                'poly_id' => $polys->where('code', 'POLI-UMUM')->first()->id,
                'name' => 'Poli Umum - Umum',
                'code_prefix' => 'B',
                'service_unit' => 'Rawat Jalan',
                'avg_service_minutes' => 12,
                'is_active' => true,
            ],
            [
                'poly_id' => $polys->where('code', 'POLI-GIGI')->first()->id,
                'name' => 'Poli Gigi',
                'code_prefix' => 'C',
                'service_unit' => 'Rawat Jalan',
                'avg_service_minutes' => 20,
                'is_active' => true,
            ],
            [
                'poly_id' => $polys->where('code', 'POLI-ANAK')->first()->id,
                'name' => 'Poli Anak',
                'code_prefix' => 'D',
                'service_unit' => 'Rawat Jalan',
                'avg_service_minutes' => 15,
                'is_active' => true,
            ],
            [
                'poly_id' => $polys->where('code', 'POLI-KANDUNGAN')->first()->id,
                'name' => 'Poli Kandungan',
                'code_prefix' => 'E',
                'service_unit' => 'Rawat Jalan',
                'avg_service_minutes' => 18,
                'is_active' => true,
            ],
            [
                'poly_id' => $polys->where('code', 'POLI-JANTUNG')->first()->id,
                'name' => 'Poli Jantung',
                'code_prefix' => 'F',
                'service_unit' => 'Rawat Jalan',
                'avg_service_minutes' => 20,
                'is_active' => true,
            ],
            
            // Supporting services (no poly_id)
            [
                'poly_id' => null,
                'name' => 'Laboratorium',
                'code_prefix' => 'L',
                'service_unit' => 'Penunjang',
                'avg_service_minutes' => 10,
                'is_active' => true,
            ],
            [
                'poly_id' => null,
                'name' => 'Farmasi',
                'code_prefix' => 'R',
                'service_unit' => 'Penunjang',
                'avg_service_minutes' => 8,
                'is_active' => true,
            ],
            [
                'poly_id' => null,
                'name' => 'Administrasi',
                'code_prefix' => 'K',
                'service_unit' => 'Administrasi',
                'avg_service_minutes' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($queueTypes as $queueType) {
            QueueType::create($queueType);
        }
    }
}
