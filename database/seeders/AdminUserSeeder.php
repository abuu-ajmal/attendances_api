<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $employee = Employee::where(
            'employee_no',
            'EMP-00001'
        )->first();

        $admin = User::updateOrCreate(
            [
                'email' => 'admin@example.com'
            ],
            [
                'name' => 'System Administrator',
                'employee_id' => $employee->id,
                'password' => Hash::make(
                    'Admin@12345'
                ),
                'is_active' => true,
            ]
        );

        $role = Role::where(
            'name',
            'super_admin'
        )->first();

        $admin->roles()->sync([
            $role->id
        ]);
    }
}