<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Poly;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $polys = Poly::all();

        $doctors = [
            [
                'poly_id' => $polys->where('code', 'POLI-UMUM')->first()->id,
                'sip_number' => 'SIP-001-2024',
                'name' => 'dr. I Made Suryawan',
                'specialization' => 'Dokter Umum',
            ],
            [
                'poly_id' => $polys->where('code', 'POLI-UMUM')->first()->id,
                'sip_number' => 'SIP-002-2024',
                'name' => 'dr. Ni Putu Ayu Dewi',
                'specialization' => 'Dokter Umum',
            ],
            [
                'poly_id' => $polys->where('code', 'POLI-GIGI')->first()->id,
                'sip_number' => 'SIP-003-2024',
                'name' => 'drg. I Wayan Agus',
                'specialization' => 'Dokter Gigi',
            ],
            [
                'poly_id' => $polys->where('code', 'POLI-ANAK')->first()->id,
                'sip_number' => 'SIP-004-2024',
                'name' => 'dr. Ni Luh Sri, Sp.A',
                'specialization' => 'Spesialis Anak',
            ],
            [
                'poly_id' => $polys->where('code', 'POLI-KANDUNGAN')->first()->id,
                'sip_number' => 'SIP-005-2024',
                'name' => 'dr. I Ketut Widana, Sp.OG',
                'specialization' => 'Spesialis Kandungan',
            ],
            [
                'poly_id' => $polys->where('code', 'POLI-JANTUNG')->first()->id,
                'sip_number' => 'SIP-006-2024',
                'name' => 'dr. I Gede Mahendra, Sp.JP',
                'specialization' => 'Spesialis Jantung',
            ],
        ];

        foreach ($doctors as $doctorData) {
            $doctor = Doctor::create($doctorData);

            // Create schedules (Monday, Wednesday, Friday)
            $days = [1, 3, 5];
            foreach ($days as $day) {
                DoctorSchedule::create([
                    'doctor_id' => $doctor->id,
                    'day_of_week' => $day,
                    'start_time' => '08:00',
                    'end_time' => '14:00',
                    'max_quota' => 20,
                ]);
            }
        }
    }
}
