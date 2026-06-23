<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@epms.local'],
            [
                'name' => 'Administrator',
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'email' => 'admin@epms.local',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'must_change_password' => true,
            ]
        );

        // Assign super-admin role if not already assigned
        if (! $admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
        }
    }
}
