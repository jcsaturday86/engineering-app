<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions grouped logically
        $permissions = [
            // Applications
            'view-applications',
            'create-applications',
            'edit-applications',
            'delete-applications',
            'submit-applications',
            'approve-applications',
            'reject-applications',
            'cancel-applications',

            // Assessments
            'view-assessments',
            'create-assessments',
            'edit-assessments',
            'finalize-assessments',

            // Zoning
            'view-zoning',
            'create-zoning',
            'edit-zoning',
            'finalize-zoning',
            'skip-zoning',

            // Billing
            'view-billing',
            'generate-billing',
            'reprint-billing',

            // Collections
            'view-collections',
            'create-collections',
            'void-collections',
            'print-receipts',

            // Permits
            'view-permits',
            'generate-permits',
            'print-permits',
            'release-permits',

            // Reports
            'view-reports',
            'export-reports',

            // Settings
            'manage-settings',
            'manage-users',
            'manage-roles',
            'manage-fee-schedules',
            'manage-signatories',

            // Online (client-facing)
            'online-apply',
            'online-upload',
            'online-track',
            'online-download',
        ];

        // Create permissions (idempotent)
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Define roles and their permissions
        $rolePermissions = [
            'super-admin' => $permissions, // All permissions

            'administrator' => collect($permissions)
                ->reject(fn (string $p) => str_starts_with($p, 'online-'))
                ->values()
                ->all(),

            'engineering-officer' => [
                // Applications (all)
                'view-applications', 'create-applications', 'edit-applications',
                'delete-applications', 'submit-applications', 'approve-applications',
                'reject-applications', 'cancel-applications',
                // Assessments (all)
                'view-assessments', 'create-assessments', 'edit-assessments', 'finalize-assessments',
                // Billing (all)
                'view-billing', 'generate-billing', 'reprint-billing',
                // Permits (all)
                'view-permits', 'generate-permits', 'print-permits', 'release-permits',
                // Reports
                'view-reports',
            ],

            'engineering-staff' => [
                // Applications (view/create/edit)
                'view-applications', 'create-applications', 'edit-applications',
                // Assessments (view/create/edit)
                'view-assessments', 'create-assessments', 'edit-assessments',
                // Billing (view only)
                'view-billing',
                // Permits (view only)
                'view-permits',
            ],

            'planning-officer' => [
                'view-applications',
                // Zoning (all)
                'view-zoning', 'create-zoning', 'edit-zoning', 'finalize-zoning', 'skip-zoning',
                // Reports
                'view-reports',
            ],

            'planning-staff' => [
                'view-applications',
                // Zoning (view/create/edit)
                'view-zoning', 'create-zoning', 'edit-zoning',
            ],

            'treasury-officer' => [
                'view-applications',
                'view-billing',
                // Collections (all)
                'view-collections', 'create-collections', 'void-collections', 'print-receipts',
                // Reports
                'view-reports',
            ],

            'treasury-staff' => [
                'view-applications',
                'view-billing',
                // Collections (view/create)
                'view-collections', 'create-collections',
                'print-receipts',
            ],

            'client' => [
                'online-apply', 'online-upload', 'online-track', 'online-download',
            ],
        ];

        // Create roles and assign permissions (idempotent)
        foreach ($rolePermissions as $roleName => $rolePerms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePerms);
        }

        // Reset cached roles and permissions after seeding
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
