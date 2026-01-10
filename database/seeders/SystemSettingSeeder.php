<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $settings = [
            [
                'key' => 'GEOFENCE_ENABLED',
                'value' => 'false',
                'description' => 'Enable geofencing for queue booking'
            ],
            [
                'key' => 'MAX_DISTANCE_METER',
                'value' => '100',
                'description' => 'Maximum distance from hospital in meters'
            ],
            [
                'key' => 'HOSPITAL_LAT',
                'value' => '-8.670458',
                'description' => 'Hospital latitude coordinate (Denpasar, Bali)'
            ],
            [
                'key' => 'HOSPITAL_LNG',
                'value' => '115.212629',
                'description' => 'Hospital longitude coordinate (Denpasar, Bali)'
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::create($setting);
        }
    }
}
