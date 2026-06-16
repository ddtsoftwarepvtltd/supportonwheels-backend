<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin User
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@ndis.com'],
            [
                'name'      => 'Super Admin',
                'email'     => 'superadmin@ndis.com',
                'password'  => Hash::make('Admin@12345'),
                'phone'     => '+61400000000',
                'user_type' => 'super_admin',
                'status'    => 'active',
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('super_admin');

        // Admin User (optional — test ke liye)
        $admin = User::firstOrCreate(
            ['email' => 'admin@ndis.com'],
            [
                'name'      => 'Admin User',
                'email'     => 'admin@ndis.com',
                'password'  => Hash::make('Admin@12345'),
                'phone'     => '+61400000001',
                'user_type' => 'admin',
                'status'    => 'active',
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        $this->command->info('✅ Super Admin aur Admin user seed ho gaye!');
        $this->command->info('   Email: superadmin@ndis.com | Password: Admin@12345');
    }
}
