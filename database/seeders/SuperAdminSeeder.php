<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'jsantana'],
            [
                'name' => 'Jesus Santana',
                'email' => 'jsantana@gestionautomotriz.local',
                'password' => Hash::make('*S4nt4n4s'),
                'role' => 'super_admin',
                'company_id' => null,
            ]
        );
    }
}
