<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Cache reset karo pehle
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ===== PERMISSIONS =====

        $permissions = [

            // --- User Management ---
            'view users',
            'create users',
            'edit users',
            'delete users',
            'block users',

            // --- Worker Management ---
            'view workers',
            'create workers',
            'edit workers',
            'delete workers',
            'approve workers',
            'block workers',

            // --- Provider Management ---
            'view providers',
            'create providers',
            'edit providers',
            'delete providers',
            'approve providers',

            // --- Participant Management ---
            'view participants',
            'create participants',
            'edit participants',
            'delete participants',

            // --- Shift Management ---
            'view shifts',
            'create shifts',
            'edit shifts',
            'delete shifts',
            'assign shifts',
            'approve shifts',
            'cancel shifts',
            'bulk upload shifts',

            // --- Timesheet ---
            'view timesheets',
            'submit timesheets',
            'approve timesheets',

            // --- Incident Management ---
            'view incidents',
            'create incidents',
            'manage incidents',

            // --- Referral & Points ---
            'view referrals',
            'manage referrals',
            'view points',
            'manage points',

            // --- Reports ---
            'view reports',
            'export reports',

            // --- Admin Panel ---
            'access admin panel',
            'manage roles',
            'manage permissions',
            'view dashboard',
            'manage settings',

            // --- Notifications ---
            'send notifications',
            'view notifications',

            // --- Ratings ---
            'view ratings',
            'submit ratings',
            'manage ratings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // ===== ROLES =====

        // 1. Super Admin — saari permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. Admin — almost sab, lekin role/permission manage nahi kar sakta
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $admin->syncPermissions(Permission::whereNotIn('name', [
            'manage roles',
            'manage permissions',
        ])->get());

        // 3. Provider
        $provider = Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'api']);
        $provider->syncPermissions([
            'view workers',
            'view shifts',
            'create shifts',
            'edit shifts',
            'cancel shifts',
            'bulk upload shifts',
            'assign shifts',
            'approve shifts',
            'view timesheets',
            'approve timesheets',
            'view incidents',
            'view referrals',
            'view points',
            'submit ratings',
            'view ratings',
            'send notifications',
            'view notifications',
            'view participants',
        ]);

        // 4. Worker
        $worker = Role::firstOrCreate(['name' => 'worker', 'guard_name' => 'api']);
        $worker->syncPermissions([
            'view shifts',
            'approve shifts',   // accept/reject shift
            'cancel shifts',
            'submit timesheets',
            'view timesheets',
            'create incidents',
            'view incidents',
            'view referrals',
            'view points',
            'view ratings',
            'view notifications',
        ]);

        // 5. Participant
        $participant = Role::firstOrCreate(['name' => 'participant', 'guard_name' => 'api']);
        $participant->syncPermissions([
            'view shifts',
            'view workers',
            'submit ratings',
            'view ratings',
            'view referrals',
            'view points',
            'view notifications',
        ]);

        $this->command->info('✅ Roles aur Permissions successfully seed ho gaye!');
    }
}
