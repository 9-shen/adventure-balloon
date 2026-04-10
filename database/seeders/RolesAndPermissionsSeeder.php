<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permissions ──────────────────────────────────────────────────────────
        $permissions = [
            // Bookings
            'view_bookings', 'create_bookings', 'edit_bookings', 'delete_bookings',
            'confirm_bookings', 'cancel_bookings', 'assign_bookings',

            // Customers
            'view_customers', 'create_customers', 'edit_customers', 'delete_customers',

            // Partners
            'view_partners', 'create_partners', 'edit_partners', 'delete_partners',

            // Flights / Dispatch
            'view_flights', 'create_flights', 'edit_flights', 'delete_flights',
            'dispatch_flights', 'manage_pilots',

            // Finance
            'view_payments', 'process_payments', 'issue_refunds',
            'view_reports', 'export_reports',

            // Settings
            'view_settings', 'edit_settings',

            // Users
            'view_users', 'create_users', 'edit_users', 'delete_users', 'assign_roles',

            // Media / Documents
            'view_media', 'upload_media', 'delete_media',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ── Roles ────────────────────────────────────────────────────────────────
        $roles = [

            // ── Full access
            'super_admin' => Permission::all()->pluck('name')->toArray(),

            // ── All access except app settings
            'admin' => [
                'view_bookings', 'create_bookings', 'edit_bookings', 'delete_bookings',
                'confirm_bookings', 'cancel_bookings', 'assign_bookings',
                'view_customers', 'create_customers', 'edit_customers', 'delete_customers',
                'view_partners', 'create_partners', 'edit_partners',
                'view_flights', 'create_flights', 'edit_flights', 'dispatch_flights',
                'view_payments', 'process_payments', 'issue_refunds',
                'view_reports', 'export_reports',
                'view_users', 'create_users', 'edit_users',
                'view_media', 'upload_media',
            ],

            // ── Operational management — bookings, dispatch, partners, reports
            'manager' => [
                'view_bookings', 'create_bookings', 'edit_bookings',
                'confirm_bookings', 'cancel_bookings', 'assign_bookings',
                'view_customers', 'create_customers', 'edit_customers',
                'view_partners', 'edit_partners',
                'view_flights', 'create_flights', 'edit_flights', 'dispatch_flights',
                'view_payments', 'view_reports',
                'view_users',
                'view_media', 'upload_media',
            ],

            // ── Finance & reporting only
            'accountant' => [
                'view_bookings', 'edit_bookings',
                'view_payments', 'process_payments', 'issue_refunds',
                'view_reports', 'export_reports',
                'view_customers',
                'view_partners',
                'view_media',
            ],

            // ── Front-desk / arrival scanning
            'greeter' => [
                'view_bookings',
                'edit_bookings',
                'view_customers',
            ],

            // ── Transport company contact — sees their dispatches only
            'transport' => [
                'view_flights',
                'view_bookings',
            ],

            // ── Driver — sees their own assigned dispatches
            'driver' => [
                'view_flights',
            ],

            // ── Partner agency — access via /partner panel only
            'partner' => [
                'view_bookings', 'create_bookings',
                'view_customers',
                'view_payments',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }

        // ── Remove obsolete roles ─────────────────────────────────────────────────
        $obsoleteRoles = ['agent', 'dispatcher', 'pilot', 'customer'];
        foreach ($obsoleteRoles as $old) {
            Role::where('name', $old)->delete();
        }

        $this->command->info('✅ Roles and permissions seeded successfully.');
        $this->command->table(
            ['Role', 'Permissions'],
            collect($roles)->map(fn ($perms, $name) => [$name, count($perms) . ' permissions'])->values()->toArray()
        );
    }
}
