<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $query = Device::query()
            ->with('employee');

        if (!$request->user()->hasRole('super_admin')) {
            $query->where(
                'employee_id',
                $request->user()->employee_id
            );
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_uuid' => [
                'required',
                'string',
                'max:255',
            ],

            'device_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'platform' => [
                'nullable',
                'string',
                'max:100',
            ],

            'os_version' => [
                'nullable',
                'string',
                'max:100',
            ],

            'app_version' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $device = Device::updateOrCreate(
            [
                'device_uuid' =>
                    $validated['device_uuid'],
            ],

            [
                'employee_id' =>
                    $request->user()->employee_id,

                'device_name' =>
                    $validated['device_name'] ?? null,

                'platform' =>
                    $validated['platform'] ?? null,

                'os_version' =>
                    $validated['os_version'] ?? null,

                'app_version' =>
                    $validated['app_version'] ?? null,

                'last_seen_at' => now(),

                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully.',
            'data' => $device,
        ], 201);
    }

    public function destroy(
        Request $request,
        Device $device
    ) {
        if (
            !$request->user()->hasRole('super_admin') &&
            $device->employee_id !==
            $request->user()->employee_id
        ) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $device->update([
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device deactivated successfully.',
        ]);
    }
}