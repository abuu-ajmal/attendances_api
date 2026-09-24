<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'units' => [
                    [
                        'name' => 'Human Resources Management',
                        'code' => 'HRM',
                    ],
                ],
            ],
            [
                'name' => 'Information and Communication Technology',
                'code' => 'ICT',
                'units' => [
                    [
                        'name' => 'Software Development',
                        'code' => 'SD',
                    ],
                    [
                        'name' => 'Infrastructure',
                        'code' => 'INF',
                    ],
                    [
                        'name' => 'ICT Support',
                        'code' => 'SUP',
                    ],
                ],
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                'units' => [
                    [
                        'name' => 'Finance Unit',
                        'code' => 'FIN-U',
                    ],
                ],
            ],
        ];

        foreach ($departments as $departmentData) {

            $units = $departmentData['units'];

            unset(
                $departmentData['units']
            );

            $department = Department::updateOrCreate(
                [
                    'code' => $departmentData['code']
                ],
                $departmentData
            );

            foreach ($units as $unit) {
                Unit::updateOrCreate(
                    [
                        'department_id' => $department->id,
                        'code' => $unit['code'],
                    ],
                    [
                        'name' => $unit['name'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}