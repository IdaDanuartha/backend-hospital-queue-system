<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::create([
            'name' => 'Super Admin',
            'username' => 'admin',
            'email' => 'admin@mail.com',
            'password' => bcrypt('123456'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Admin::create([
            'user_id' => $adminUser->id,
            'position' => 'System Administrator',
            'department' => 'IT & Operations',
        ]);
    }
}
