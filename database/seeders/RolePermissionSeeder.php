<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'System Administrator',
            ],

            [
                'name' => 'hr_manager',
                'display_name' => 'HR Manager',
            ],

            [
                'name' => 'ict_manager',
                'display_name' => 'ICT Manager',
            ],

            [
                'name' => 'unit_head',
                'display_name' => 'Unit Head',
            ],

            [
                'name' => 'secretary',
                'display_name' => 'Secretary',
            ],

            [
                'name' => 'director',
                'display_name' => 'Director',
            ],

            [
                'name' => 'staff',
                'display_name' => 'Staff',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Create Roles
        |--------------------------------------------------------------------------
        */

        foreach ($roles as $roleData) {

            Role::updateOrCreate(
                [
                    'name' => $roleData['name'],
                ],
                [
                    'display_name' => $roleData['display_name'],
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            [
                'name' => 'view_dashboard',
                'display_name' => 'View Dashboard',
            ],

            [
                'name' => 'view_employees',
                'display_name' => 'View Employees',
            ],

            [
                'name' => 'create_employee',
                'display_name' => 'Create Employee',
            ],

            [
                'name' => 'update_employee',
                'display_name' => 'Update Employee',
            ],

            [
                'name' => 'delete_employee',
                'display_name' => 'Delete Employee',
            ],

            [
                'name' => 'view_departments',
                'display_name' => 'View Departments',
            ],

            [
                'name' => 'manage_departments',
                'display_name' => 'Manage Departments',
            ],

            [
                'name' => 'view_units',
                'display_name' => 'View Units',
            ],

            [
                'name' => 'manage_units',
                'display_name' => 'Manage Units',
            ],

            [
                'name' => 'record_attendance',
                'display_name' => 'Record Attendance',
            ],

            [
                'name' => 'view_attendance',
                'display_name' => 'View Attendance',
            ],

            [
                'name' => 'view_all_attendance',
                'display_name' => 'View All Attendance',
            ],

            [
                'name' => 'view_department_attendance',
                'display_name' => 'View Department Attendance',
            ],

            [
                'name' => 'view_unit_attendance',
                'display_name' => 'View Unit Attendance',
            ],

            [
                'name' => 'view_reports',
                'display_name' => 'View Reports',
            ],

            [
                'name' => 'export_reports',
                'display_name' => 'Export Reports',
            ],

            [
                'name' => 'manage_users',
                'display_name' => 'Manage Users',
            ],

            [
                'name' => 'manage_roles',
                'display_name' => 'Manage Roles',
            ],

            [
                'name' => 'manage_devices',
                'display_name' => 'Manage Devices',
            ],

            [
                'name' => 'view_audit_logs',
                'display_name' => 'View Audit Logs',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Create Permissions
        |--------------------------------------------------------------------------
        */

        foreach ($permissions as $permissionData) {

            Permission::updateOrCreate(
                [
                    'name' => $permissionData['name'],
                ],
                [
                    'display_name' =>
                        $permissionData['display_name'],
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Get Roles
        |--------------------------------------------------------------------------
        */

        $superAdmin = Role::where(
            'name',
            'super_admin'
        )->firstOrFail();

        $hrManager = Role::where(
            'name',
            'hr_manager'
        )->firstOrFail();

        $ictManager = Role::where(
            'name',
            'ict_manager'
        )->firstOrFail();

        $unitHead = Role::where(
            'name',
            'unit_head'
        )->firstOrFail();

        $secretary = Role::where(
            'name',
            'secretary'
        )->firstOrFail();

        $director = Role::where(
            'name',
            'director'
        )->firstOrFail();

        $staff = Role::where(
            'name',
            'staff'
        )->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Get Permissions
        |--------------------------------------------------------------------------
        */

        $permission = fn (string $name) =>
            Permission::where(
                'name',
                $name
            )->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        */

        $superAdmin->permissions()->sync(
            Permission::pluck('id')->toArray()
        );

        /*
        |--------------------------------------------------------------------------
        | HR Manager
        |--------------------------------------------------------------------------
        */

        $hrManager->permissions()->sync([
            $permission('view_dashboard')->id,

            $permission('view_employees')->id,
            $permission('create_employee')->id,
            $permission('update_employee')->id,

            $permission('view_departments')->id,
            $permission('view_units')->id,

            $permission('record_attendance')->id,
            $permission('view_attendance')->id,
            $permission('view_department_attendance')->id,

            $permission('view_reports')->id,
            $permission('export_reports')->id,

            $permission('manage_devices')->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | ICT Manager
        |--------------------------------------------------------------------------
        */

        $ictManager->permissions()->sync([
            $permission('view_dashboard')->id,

            $permission('view_employees')->id,

            $permission('view_departments')->id,
            $permission('view_units')->id,

            $permission('record_attendance')->id,
            $permission('view_attendance')->id,
            $permission('view_unit_attendance')->id,

            $permission('view_reports')->id,
            $permission('export_reports')->id,

            $permission('manage_devices')->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Unit Head
        |--------------------------------------------------------------------------
        */

        $unitHead->permissions()->sync([
            $permission('view_dashboard')->id,

            $permission('record_attendance')->id,
            $permission('view_attendance')->id,

            $permission('view_unit_attendance')->id,

            $permission('view_reports')->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Secretary
        |--------------------------------------------------------------------------
        */

        $secretary->permissions()->sync([
            $permission('view_dashboard')->id,

            $permission('view_employees')->id,

            $permission('view_attendance')->id,
            $permission('view_all_attendance')->id,

            $permission('view_reports')->id,
            $permission('export_reports')->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Director
        |--------------------------------------------------------------------------
        */

        $director->permissions()->sync([
            $permission('view_dashboard')->id,

            $permission('view_employees')->id,

            $permission('view_attendance')->id,
            $permission('view_all_attendance')->id,

            $permission('view_reports')->id,
            $permission('export_reports')->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Staff
        |--------------------------------------------------------------------------
        */

        $staff->permissions()->sync([
            $permission('view_dashboard')->id,

            $permission('record_attendance')->id,
            $permission('view_attendance')->id,
        ]);
    }
}