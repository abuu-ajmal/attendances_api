<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\ScopeService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        protected ScopeService $scopeService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $employeeIds = $this
            ->scopeService
            ->employeeIds($user);

        $today = Carbon::today(
            'Africa/Dar_es_Salaam'
        );

        $totalEmployees = Employee::query()
            ->whereIn('id', $employeeIds)
            ->where('employment_status', 'active')
            ->count();

        $checkedIn = AttendanceRecord::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('occurred_at', $today)
            ->where('type', 'check_in')
            ->distinct('employee_id')
            ->count('employee_id');

        $checkedOut = AttendanceRecord::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('occurred_at', $today)
            ->where('type', 'check_out')
            ->distinct('employee_id')
            ->count('employee_id');

        $notCheckedIn = max(
            0,
            $totalEmployees - $checkedIn
        );

        return response()->json([
            'success' => true,

            'data' => [
                'date' => $today->toDateString(),

                'total_employees' =>
                    $totalEmployees,

                'checked_in' =>
                    $checkedIn,

                'checked_out' =>
                    $checkedOut,

                'not_checked_in' =>
                    $notCheckedIn,
            ],
        ]);
    }
}