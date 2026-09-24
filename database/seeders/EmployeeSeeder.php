<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $ict = Department::where(
            'code',
            'ICT'
        )->first();

        $unit = Unit::where(
            'code',
            'SD'
        )->first();

        Employee::updateOrCreate(
            [
                'employee_no' => 'EMP-00001'
            ],
            [
                'first_name' => 'System',
                'middle_name' => null,
                'last_name' => 'Administrator',
                'phone' => '0777000000',
                'email' => 'admin@example.com',
                'department_id' => $ict->id,
                'unit_id' => $unit->id,
                'job_title' => 'System Administrator',
                'employment_status' => 'active',
            ]
        );
    }
}