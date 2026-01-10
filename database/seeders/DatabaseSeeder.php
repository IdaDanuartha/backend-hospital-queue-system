<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SystemSettingSeeder::class,
            AdminSeeder::class,
            PolySeeder::class,
            DoctorSeeder::class,
            QueueTypeSeeder::class,
            StaffSeeder::class,
        ]);
    }
}

