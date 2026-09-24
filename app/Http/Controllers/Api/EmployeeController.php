<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\ScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function __construct(
        protected ScopeService $scopeService
    ) {}

    /**
     * Display a list of employees.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Employee::query()
            ->with([
                'department',
                'unit',
                'supervisor',
                'user.roles',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Apply employee scope
        |--------------------------------------------------------------------------
        */

        $this->scopeService->applyEmployeeScope(
            $query,
            $user
        );

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim(
                $request->input('search')
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'employee_no',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'first_name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'middle_name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'last_name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'phone',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'email',
                    'like',
                    "%{$search}%"
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Department filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('department_id')) {

            $query->where(
                'department_id',
                $request->integer('department_id')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Unit filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('unit_id')) {

            $query->where(
                'unit_id',
                $request->integer('unit_id')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Employment status filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('employment_status')) {

            $query->where(
                'employment_status',
                $request->input('employment_status')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = min(
            max(
                $request->integer('per_page', 20),
                1
            ),
            100
        );

        return response()->json([
            'success' => true,
            'data' => $query
                ->latest()
                ->paginate($perPage),
        ]);
    }


    /**
     * Store a new employee and automatically create login account.
     */
    public function store(Request $request)
    {
        try {

            $currentUser = $request->user();

            /*
            |--------------------------------------------------------------------------
            | 1. Authorization
            |--------------------------------------------------------------------------
            */

            if (
                !$currentUser ||
                !$currentUser->hasPermission('create_employee')
            ) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'You are not authorized to create employees.',
                ], 403);
            }


            /*
            |--------------------------------------------------------------------------
            | 2. Validate employee information
            |--------------------------------------------------------------------------
            */

            $validated = $request->validate([

                'employee_no' => [
                    'required',
                    'string',
                    'max:50',
                    'unique:employees,employee_no',
                ],

                'first_name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'middle_name' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'last_name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'phone' => [
                    'nullable',
                    'string',
                    'max:30',
                    'unique:employees,phone',
                ],

                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    'unique:employees,email',
                ],

                'gender' => [
                    'nullable',
                    'string',
                    'max:30',
                ],

                'date_of_birth' => [
                    'nullable',
                    'date',
                ],

                'department_id' => [
                    'nullable',
                    'integer',
                    'exists:departments,id',
                ],

                'unit_id' => [
                    'nullable',
                    'integer',
                    'exists:units,id',
                ],

                'job_title' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'supervisor_id' => [
                    'nullable',
                    'integer',
                    'exists:employees,id',
                ],

                'employment_status' => [
                    'required',
                    'string',
                    'max:50',
                ],

                'profile_photo' => [
                    'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:2048',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | 3. Check unit belongs to selected department
            |--------------------------------------------------------------------------
            */

            if (
                !empty($validated['unit_id']) &&
                !empty($validated['department_id'])
            ) {

                $unitBelongsToDepartment =
                    DB::table('units')
                        ->where(
                            'id',
                            $validated['unit_id']
                        )
                        ->where(
                            'department_id',
                            $validated['department_id']
                        )
                        ->exists();

                if (!$unitBelongsToDepartment) {

                    throw ValidationException::withMessages([
                        'unit_id' => [
                            'The selected unit does not belong to the selected department.'
                        ],
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 4. Create employee + user account + role
            |--------------------------------------------------------------------------
            */

            $result = DB::transaction(function () use (
                $validated,
                $request
            ) {

                /*
                |--------------------------------------------------------------------------
                | Upload profile photo
                |--------------------------------------------------------------------------
                */

                if ($request->hasFile('profile_photo')) {

                    $validated['profile_photo'] =
                        $request
                            ->file('profile_photo')
                            ->store(
                                'employees/profile-photos',
                                'public'
                            );
                }


                /*
                |--------------------------------------------------------------------------
                | Create employee
                |--------------------------------------------------------------------------
                */

                $employee = Employee::create(
                    $validated
                );


                /*
                |--------------------------------------------------------------------------
                | Create login credentials
                |--------------------------------------------------------------------------
                |
                | Username = Employee Number
                | Password = Employee Number + @123
                |
                */

                $username = $employee->employee_no;

                $temporaryPassword =
                    $employee->employee_no . '@123';


                /*
                |--------------------------------------------------------------------------
                | User email
                |--------------------------------------------------------------------------
                |
                | Kama employee hana email,
                | tunatumia internal email.
                |
                */

                $userEmail =
                    $employee->email
                    ?: strtolower(
                        $employee->employee_no
                    ) . '@staff.local';


                /*
                |--------------------------------------------------------------------------
                | Check email uniqueness
                |--------------------------------------------------------------------------
                */

                if (
                    User::where(
                        'email',
                        $userEmail
                    )->exists()
                ) {

                    throw ValidationException::withMessages([
                        'email' => [
                            'A user account with this email already exists.'
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Create user account
                |--------------------------------------------------------------------------
                */

                $staffUser = User::create([
                    'name' =>
                        trim(
                            implode(' ', array_filter([
                                $employee->first_name,
                                $employee->middle_name,
                                $employee->last_name,
                            ]))
                        ),

                    'email' => $userEmail,

                    'password' =>
                        $temporaryPassword,

                    'employee_id' =>
                        $employee->id,

                    'is_active' => true,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Get staff role
                |--------------------------------------------------------------------------
                */

                $staffRole = Role::where(
                    'name',
                    'staff'
                )->first();


                if (!$staffRole) {

                    throw new \RuntimeException(
                        'The staff role does not exist. Please run the role seeder first.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Assign staff role
                |--------------------------------------------------------------------------
                */

                $staffUser->roles()->attach(
                    $staffRole->id
                );


                /*
                |--------------------------------------------------------------------------
                | Return created employee + credentials
                |--------------------------------------------------------------------------
                */

                return [
                    'employee' =>
                        $employee,

                    'username' =>
                        $username,

                    'temporary_password' =>
                        $temporaryPassword,

                    'role' =>
                        $staffRole->name,
                ];
            });


            /*
            |--------------------------------------------------------------------------
            | 5. Load relationships
            |--------------------------------------------------------------------------
            */

            $employee =
                $result['employee'];

            $employee->load([
                'department',
                'unit',
                'supervisor',
                'user.roles',
            ]);


            /*
            |--------------------------------------------------------------------------
            | 6. Return successful response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,

                'message' =>
                    'Mtumishi ameongezwa na akaunti yake ya kuingia imetengenezwa.',

                'data' =>
                    $employee,

                'credentials' => [

                    'username' =>
                        $result['username'],

                    'password' =>
                        $result['temporary_password'],

                    'role' =>
                        $result['role'],
                ],

            ], 201);


        } catch (ValidationException $e) {

            throw $e;

        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Imeshindikana kuongeza mtumishi.',

                'error' =>
                    $e->getMessage(),

            ], 500);
        }
    }


    /**
     * Display a specific employee.
     */
    public function show(
        Request $request,
        Employee $employee
    ) {

        if (
            !$this->scopeService->canViewEmployee(
                $request->user(),
                $employee
            )
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to view this employee.',
            ], 403);
        }

        return response()->json([
            'success' => true,

            'data' =>
                $employee->load([

                    'department',

                    'unit',

                    'supervisor',

                    'subordinates',

                    'user.roles',

                    'attendanceRecords' => function ($query) {

                        $query
                            ->latest('occurred_at')
                            ->limit(30);
                    },
                ]),
        ]);
    }


    /**
     * Update an employee.
     */
    public function update(
        Request $request,
        Employee $employee
    ) {

        $user = $request->user();


        /*
        |--------------------------------------------------------------------------
        | Authorization
        |--------------------------------------------------------------------------
        */

        if (
            !$user->hasPermission(
                'update_employee'
            )
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to update employees.',
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Scope
        |--------------------------------------------------------------------------
        */

        if (
            !$this->scopeService->canViewEmployee(
                $user,
                $employee
            )
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to update this employee.',
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'employee_no' => [
                'required',
                'string',
                'max:50',

                Rule::unique(
                    'employees',
                    'employee_no'
                )->ignore($employee->id),
            ],

            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'middle_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'last_name' => [
                'required',
                'string',
                'max:100',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',

                Rule::unique(
                    'employees',
                    'phone'
                )->ignore($employee->id),
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',

                Rule::unique(
                    'employees',
                    'email'
                )->ignore($employee->id),
            ],

            'gender' => [
                'nullable',
                'string',
                'max:30',
            ],

            'date_of_birth' => [
                'nullable',
                'date',
            ],

            'department_id' => [
                'nullable',
                'integer',
                'exists:departments,id',
            ],

            'unit_id' => [
                'nullable',
                'integer',
                'exists:units,id',
            ],

            'job_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'supervisor_id' => [
                'nullable',
                'integer',
                'exists:employees,id',

                'not_in:' .
                $employee->id,
            ],

            'employment_status' => [
                'required',
                'string',
                'max:50',
            ],

            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'remove_profile_photo' => [
                'nullable',
                'boolean',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Validate unit belongs to department
        |--------------------------------------------------------------------------
        */

        if (
            !empty($validated['unit_id']) &&
            !empty($validated['department_id'])
        ) {

            $unitBelongsToDepartment =
                DB::table('units')
                    ->where(
                        'id',
                        $validated['unit_id']
                    )
                    ->where(
                        'department_id',
                        $validated['department_id']
                    )
                    ->exists();

            if (!$unitBelongsToDepartment) {

                throw ValidationException::withMessages([
                    'unit_id' => [
                        'The selected unit does not belong to the selected department.'
                    ],
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Update employee
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $employee,
            $validated,
            $request
        ) {

            /*
            |--------------------------------------------------------------------------
            | Remove old photo
            |--------------------------------------------------------------------------
            */

            if (
                !empty(
                    $validated['remove_profile_photo']
                ) &&
                $employee->profile_photo
            ) {

                Storage::disk('public')
                    ->delete(
                        $employee->profile_photo
                    );

                $validated['profile_photo'] = null;
            }


            /*
            |--------------------------------------------------------------------------
            | New photo
            |--------------------------------------------------------------------------
            */

            if (
                $request->hasFile(
                    'profile_photo'
                )
            ) {

                if (
                    $employee->profile_photo
                ) {

                    Storage::disk('public')
                        ->delete(
                            $employee->profile_photo
                        );
                }

                $validated['profile_photo'] =
                    $request
                        ->file('profile_photo')
                        ->store(
                            'employees/profile-photos',
                            'public'
                        );
            }


            /*
            |--------------------------------------------------------------------------
            | Remove helper field
            |--------------------------------------------------------------------------
            */

            unset(
                $validated[
                    'remove_profile_photo'
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | Update employee
            |--------------------------------------------------------------------------
            */

            $employee->update(
                $validated
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Return updated employee
        |--------------------------------------------------------------------------
        */

        $employee->refresh();

        $employee->load([
            'department',
            'unit',
            'supervisor',
            'user.roles',
        ]);


        return response()->json([
            'success' => true,

            'message' =>
                'Employee updated successfully.',

            'data' =>
                $employee,
        ]);
    }


    /**
     * Delete an employee.
     */
    public function destroy(
        Request $request,
        Employee $employee
    ) {

        $user = $request->user();


        /*
        |--------------------------------------------------------------------------
        | Authorization
        |--------------------------------------------------------------------------
        */

        if (
            !$user->hasPermission(
                'delete_employee'
            )
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to delete employees.',
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Scope
        |--------------------------------------------------------------------------
        */

        if (
            !$this->scopeService->canViewEmployee(
                $user,
                $employee
            )
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to delete this employee.',
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent deletion when attendance exists
        |--------------------------------------------------------------------------
        */

        if (
            method_exists(
                $employee,
                'attendanceRecords'
            ) &&
            $employee
                ->attendanceRecords()
                ->exists()
        ) {

            return response()->json([
                'success' => false,

                'message' =>
                    'This employee cannot be deleted because attendance records already exist. Deactivate the employee instead.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Delete employee
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $employee
        ) {

            if (
                $employee->profile_photo
            ) {

                Storage::disk('public')
                    ->delete(
                        $employee->profile_photo
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Delete linked user account
            |--------------------------------------------------------------------------
            */

            if ($employee->user) {
                $employee->user->roles()->detach();
                $employee->user->delete();
            }


            /*
            |--------------------------------------------------------------------------
            | Delete employee
            |--------------------------------------------------------------------------
            */

            $employee->delete();
        });


        return response()->json([
            'success' => true,

            'message' =>
                'Employee deleted successfully.',
        ]);
    }
}

