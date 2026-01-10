<?php

namespace Database\Seeders;

use App\Models\Poly;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $polys = Poly::all();

        $staffData = [
            [
                'name' => 'Ni Luh Ayu Prameswari',
                'username' => 'staff_umum',
                'email' => 'staff.umum@mail.com',
                'poly_code' => 'POLI-UMUM',
                'staff_code' => 'STF-001',
            ],
            [
                'name' => 'I Made Wirawan',
                'username' => 'staff_gigi',
                'email' => 'staff.gigi@mail.com',
                'poly_code' => 'POLI-GIGI',
                'staff_code' => 'STF-002',
            ],
            [
                'name' => 'Ni Kadek Sari',
                'username' => 'staff_anak',
                'email' => 'staff.anak@mail.com',
                'poly_code' => 'POLI-ANAK',
                'staff_code' => 'STF-003',
            ],
        ];

        foreach ($staffData as $data) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => bcrypt('123456'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $poly = $polys->where('code', $data['poly_code'])->first();

            Staff::create([
                'user_id' => $user->id,
                'poly_id' => $poly->id,
                'code' => $data['staff_code'],
                'is_active' => true,
            ]);
        }
    }
}
