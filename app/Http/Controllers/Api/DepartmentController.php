<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,

            'data' => Department::query()
                ->withCount('employees')
                ->with('units')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'unique:departments,code',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $department = Department::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'is_active' =>
                $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Department created successfully.',
            'data' => $department,
        ], 201);
    }

    public function show(Department $department)
    {
        return response()->json([
            'success' => true,

            'data' => $department->load([
                'units',
                'employees',
            ]),
        ]);
    }

    public function update(
        Request $request,
        Department $department
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique(
                    'departments',
                    'code'
                )->ignore($department->id),
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $department->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'is_active' =>
                $validated['is_active']
                ?? $department->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Department updated successfully.',
            'data' => $department,
        ]);
    }

    public function destroy(Department $department)
    {
        if ($department->employees()->exists()) {
            return response()->json([
                'message' =>
                    'Cannot delete department containing employees.'
            ], 422);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully.',
        ]);
    }
}