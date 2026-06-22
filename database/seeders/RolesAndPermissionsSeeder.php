<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ===== PERMISSIONS =====
        $permissions = [

            // --- User / Auth ---
            'view users', 'create users', 'edit users', 'delete users', 'block users',

            // --- Worker ---
            'view workers', 'create workers', 'edit workers', 'delete workers',
            'approve workers', 'block workers',

            // --- Provider ---
            'view providers', 'create providers', 'edit providers',
            'delete providers', 'approve providers',

            // --- Participant / Customer ---
            'view participants', 'create participants', 'edit participants', 'delete participants',

            // --- Bookings (Home Services) ---
            'bookings.view', 'bookings.create', 'bookings.manage',

            // --- Customers (Home Services) ---
            'customers.view', 'customers.manage',

            // --- Providers (Home Services) ---
            'providers.view', 'providers.approve', 'providers.manage',

            // --- Services ---
            'services.manage',

            // --- Finance ---
            'finance.view', 'finance.manage',

            // --- Coupons ---
            'coupons.manage',

            // --- Shifts ---
            'view shifts', 'create shifts', 'edit shifts', 'delete shifts',
            'assign shifts', 'approve shifts', 'cancel shifts', 'bulk upload shifts',

            // --- Timesheets ---
            'view timesheets', 'submit timesheets', 'approve timesheets',

            // --- Incidents ---
            'view incidents', 'create incidents', 'manage incidents',

            // --- Referrals & Points ---
            'view referrals', 'manage referrals', 'view points', 'manage points',

            // --- Reports ---
            'view reports', 'export reports',

            // --- Admin Panel ---
            'access admin panel', 'manage roles', 'manage permissions',
            'view dashboard', 'manage settings',

            // --- Notifications ---
            'send notifications', 'view notifications',

            // --- Ratings ---
            'view ratings', 'submit ratings', 'manage ratings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // ===== ROLES =====

        // 1. Super Admin — saari permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. Admin
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $admin->syncPermissions(Permission::whereNotIn('name', [
            'manage roles', 'manage permissions',
        ])->get());

        // 3. Ops Admin — bookings, providers, customers manage kar sakta hai
        $opsAdmin = Role::firstOrCreate(['name' => 'ops_admin', 'guard_name' => 'api']);
        $opsAdmin->syncPermissions([
            'view dashboard', 'access admin panel',
            'bookings.view', 'bookings.manage',
            'customers.view', 'customers.manage',
            'providers.view', 'providers.approve', 'providers.manage',
            'view reports',
            'view users',
            'view notifications', 'send notifications',
        ]);

        // 4. Finance Admin — finance aur payouts manage karta hai
        $financeAdmin = Role::firstOrCreate(['name' => 'finance_admin', 'guard_name' => 'api']);
        $financeAdmin->syncPermissions([
            'view dashboard', 'access admin panel',
            'finance.view', 'finance.manage',
            'bookings.view',
            'view reports', 'export reports',
        ]);

        // 5. Provider (Home Services)
        $provider = Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'api']);
        $provider->syncPermissions([
            'view shifts', 'create shifts', 'edit shifts', 'cancel shifts',
            'assign shifts', 'approve shifts', 'bulk upload shifts',
            'view timesheets', 'approve timesheets',
            'view incidents',
            'view referrals', 'view points',
            'submit ratings', 'view ratings',
            'send notifications', 'view notifications',
            'view participants', 'view workers',
        ]);

        // 6. Customer (Home Services) — sirf apni bookings, payments
        $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);
        $customer->syncPermissions([
            'view notifications',
            'submit ratings',
            'view ratings',
        ]);

        // 7. Worker (NDIS)
        $worker = Role::firstOrCreate(['name' => 'worker', 'guard_name' => 'api']);
        $worker->syncPermissions([
            'view shifts', 'approve shifts', 'cancel shifts',
            'submit timesheets', 'view timesheets',
            'create incidents', 'view incidents',
            'view referrals', 'view points',
            'view ratings', 'view notifications',
        ]);

        // 8. Participant (NDIS)
        $participant = Role::firstOrCreate(['name' => 'participant', 'guard_name' => 'api']);
        $participant->syncPermissions([
            'view shifts', 'view workers',
            'submit ratings', 'view ratings',
            'view referrals', 'view points',
            'view notifications',
        ]);

        $this->command->info('Roles aur Permissions seed ho gaye!');
        $this->command->table(
            ['Role', 'Permissions Count'],
            [
                ['super_admin',    Permission::count()],
                ['admin',          $admin->permissions()->count()],
                ['ops_admin',      $opsAdmin->permissions()->count()],
                ['finance_admin',  $financeAdmin->permissions()->count()],
                ['provider',       $provider->permissions()->count()],
                ['customer',       $customer->permissions()->count()],
                ['worker',         $worker->permissions()->count()],
                ['participant',    $participant->permissions()->count()],
            ]
        );
    }
}
