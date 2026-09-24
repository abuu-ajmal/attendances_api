<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::query()
            ->with('department')
            ->withCount('employees');

        if ($request->filled('department_id')) {
            $query->where(
                'department_id',
                $request->department_id
            );
        }

        return response()->json([
            'success' => true,
            'data' => $query
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => [
                'required',
                'exists:departments,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $exists = Unit::query()
            ->where(
                'department_id',
                $validated['department_id']
            )
            ->where(
                'code',
                $validated['code']
            )
            ->exists();

        if ($exists) {
            return response()->json([
                'message' =>
                    'Unit code already exists in this department.'
            ], 422);
        }

        $unit = Unit::create([
            'department_id' =>
                $validated['department_id'],

            'name' =>
                $validated['name'],

            'code' =>
                strtoupper($validated['code']),

            'is_active' =>
                $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Unit created successfully.',
            'data' => $unit->load('department'),
        ], 201);
    }

    public function show(Unit $unit)
    {
        return response()->json([
            'success' => true,
            'data' => $unit->load([
                'department',
                'employees',
            ]),
        ]);
    }

    public function update(
        Request $request,
        Unit $unit
    ) {
        $validated = $request->validate([
            'department_id' => [
                'required',
                'exists:departments,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $unit->update([
            'department_id' =>
                $validated['department_id'],

            'name' =>
                $validated['name'],

            'code' =>
                strtoupper($validated['code']),

            'is_active' =>
                $validated['is_active']
                ?? $unit->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Unit updated successfully.',
            'data' => $unit->load('department'),
        ]);
    }

    public function destroy(Unit $unit)
    {
        if ($unit->employees()->exists()) {
            return response()->json([
                'message' =>
                    'Cannot delete unit containing employees.'
            ], 422);
        }

        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unit deleted successfully.',
        ]);
    }
}