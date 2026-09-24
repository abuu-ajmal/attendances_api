<?php

namespace App\Http\Controllers\Api;
use App\Models\Employee;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use App\Services\ScopeService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected ScopeService $scopeService,
        protected AuditLogService $auditLogService
    ) {}

    public function store(StoreAttendanceRequest $request)
    {
        try {

            $attendance = $this->attendanceService->record(
                user: $request->user(),
                type: $request->type,
                occurredAt: $request->occurred_at,
                latitude: $request->latitude,
                longitude: $request->longitude,
                accuracy: $request->accuracy,
                photo: $request->file('photo'),
                deviceId: $request->device_id,
                remarks: $request->remarks,
                uuid: $request->uuid,
            );

            return response()->json([
                'success' => true,
                'message' => 'Attendance recorded successfully.',
                'data' => $attendance->load('employee'),
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function myAttendance(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'Employee profile not found.'
            ], 404);
        }

        $query = AttendanceRecord::query()
            ->where(
                'employee_id',
                $employee->id
            );

        if ($request->filled('from')) {
            $query->whereDate(
                'occurred_at',
                '>=',
                $request->from
            );
        }

        if ($request->filled('to')) {
            $query->whereDate(
                'occurred_at',
                '<=',
                $request->to
            );
        }

        return response()->json([
            'success' => true,
            'data' => $query
                ->latest('occurred_at')
                ->paginate(
                    $request->integer('per_page', 30)
                ),
        ]);
    }

  public function index(Request $request)
{
    $user = $request->user();

    $query = AttendanceRecord::query()
        ->with([
            'employee.department',
            'employee.unit',
        ])
        ->whereIn(
            'employee_id',
            $this->scopeService->employeeIds($user)
        );

    if ($request->filled('from')) {
        $query->whereDate(
            'occurred_at',
            '>=',
            $request->from
        );
    }

    if ($request->filled('to')) {
        $query->whereDate(
            'occurred_at',
            '<=',
            $request->to
        );
    }

    if ($request->filled('employee_id')) {
        $query->where(
            'employee_id',
            $request->employee_id
        );
    }

    if ($request->filled('type')) {
        $query->where(
            'type',
            $request->type
        );
    }

    return response()->json([
        'success' => true,
        'data' => $query
            ->latest('occurred_at')
            ->paginate(
                $request->integer('per_page', 50)
            ),
    ]);
}
}