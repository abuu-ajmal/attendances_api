<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\ScopeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ScopeService $scopeService
    ) {}

    /**
     * Attendance Report
     *
     * Supports:
     * - daily
     * - weekly
     * - monthly
     *
     * Example:
     * /api/reports/attendance?type=daily&date=2026-10-05
     * /api/reports/attendance?type=weekly&from=2026-10-05&to=2026-10-11
     * /api/reports/attendance?type=monthly&month=2026-10
     */
    public function attendance(Request $request)
    {
        $request->validate([
            'type' => [
                'nullable',
                'in:daily,weekly,monthly',
            ],

            'date' => [
                'nullable',
                'date',
            ],

            'from' => [
                'nullable',
                'date',
            ],

            'to' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],

            'month' => [
                'nullable',
                'date_format:Y-m',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Report Type
        |--------------------------------------------------------------------------
        */

        $type = $request->input('type', 'daily');

        /*
        |--------------------------------------------------------------------------
        | Determine Date Range
        |--------------------------------------------------------------------------
        */

        switch ($type) {

            /**
             * DAILY REPORT
             */
            case 'daily':

                $date = $request->input(
                    'date',
                    now()->format('Y-m-d')
                );

                $from = Carbon::parse($date)
                    ->startOfDay();

                $to = Carbon::parse($date)
                    ->endOfDay();

                break;


            /**
             * WEEKLY REPORT
             */
            case 'weekly':

                if ($request->filled('from')) {

                    $from = Carbon::parse(
                        $request->from
                    )->startOfDay();

                    $to = Carbon::parse(
                        $request->input(
                            'to',
                            $from->copy()
                                ->endOfWeek()
                                ->format('Y-m-d')
                        )
                    )->endOfDay();

                } else {

                    $from = now()
                        ->startOfWeek()
                        ->startOfDay();

                    $to = now()
                        ->endOfWeek()
                        ->endOfDay();
                }

                break;


            /**
             * MONTHLY REPORT
             */
            case 'monthly':

                if ($request->filled('month')) {

                    $month = Carbon::createFromFormat(
                        'Y-m',
                        $request->month
                    );

                } else {

                    $month = now();
                }

                $from = $month
                    ->copy()
                    ->startOfMonth()
                    ->startOfDay();

                $to = $month
                    ->copy()
                    ->endOfMonth()
                    ->endOfDay();

                break;


            default:

                abort(
                    422,
                    'Invalid report type.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Get Employees Within User Scope
        |--------------------------------------------------------------------------
        */

        $employeeIds =
            $this->scopeService
                ->employeeIds(
                    $request->user()
                );

        /*
        |--------------------------------------------------------------------------
        | Employees
        |--------------------------------------------------------------------------
        */

        $employees = Employee::query()
            ->with([
                'department',
                'unit',
            ])
            ->whereIn(
                'id',
                $employeeIds
            )
            ->where(
                'employment_status',
                'active'
            )
            ->orderBy('employee_no')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Attendance Records
        |--------------------------------------------------------------------------
        */

        $records = AttendanceRecord::query()
            ->with([
                'employee.department',
                'employee.unit',
            ])
            ->whereIn(
                'employee_id',
                $employeeIds
            )
            ->whereBetween(
                'occurred_at',
                [
                    $from,
                    $to,
                ]
            )
            ->orderBy('occurred_at')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Group Attendance By Employee
        |--------------------------------------------------------------------------
        */

        $employeeAttendance = [];

        foreach ($employees as $employee) {

            $employeeRecords = $records
                ->where(
                    'employee_id',
                    $employee->id
                );

            /*
            |--------------------------------------------------------------------------
            | Check In
            |--------------------------------------------------------------------------
            */

            $checkIn = $employeeRecords
                ->where(
                    'type',
                    'check_in'
                )
                ->sortBy('occurred_at')
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Check Out
            |--------------------------------------------------------------------------
            */

            $checkOut = $employeeRecords
                ->where(
                    'type',
                    'check_out'
                )
                ->sortByDesc('occurred_at')
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Check-In Time
            |--------------------------------------------------------------------------
            */

            $checkInTime = null;

            if ($checkIn) {

                $checkInTime = Carbon::parse(
                    $checkIn->occurred_at
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Check-Out Time
            |--------------------------------------------------------------------------
            */

            $checkOutTime = null;

            if ($checkOut) {

                $checkOutTime = Carbon::parse(
                    $checkOut->occurred_at
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Late Calculation
            |--------------------------------------------------------------------------
            |
            | Official working time:
            | 08:00 AM
            |
            */

            $isLate = false;
            $lateMinutes = 0;

            if ($checkInTime) {

                $workStart = $checkInTime
                    ->copy()
                    ->startOfDay()
                    ->setTime(
                        8,
                        0,
                        0
                    );

                if ($checkInTime->greaterThan($workStart)) {

                    $isLate = true;

                    $lateMinutes =
                        $workStart->diffInMinutes(
                            $checkInTime
                        );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Attendance Status
            |--------------------------------------------------------------------------
            */

            if (!$checkIn) {

                $status = 'absent';

            } elseif ($isLate) {

                $status = 'late';

            } else {

                $status = 'present';
            }

            $employeeAttendance[] = [

                'employee_id' =>
                    $employee->id,

                'employee_no' =>
                    $employee->employee_no,

                'employee_name' =>
                    trim(
                        ($employee->first_name ?? '') . ' ' .
                        ($employee->middle_name ?? '') . ' ' .
                        ($employee->last_name ?? '')
                    ),

                'department' =>
                    $employee->department?->name,

                'unit' =>
                    $employee->unit?->name,

                'check_in' =>
                    $checkInTime
                        ? $checkInTime->format(
                            'Y-m-d H:i:s'
                        )
                        : null,

                'check_out' =>
                    $checkOutTime
                        ? $checkOutTime->format(
                            'Y-m-d H:i:s'
                        )
                        : null,

                'late_minutes' =>
                    $lateMinutes,

                'status' =>
                    $status,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $totalEmployees =
            count($employeeAttendance);

        $present =
            collect($employeeAttendance)
                ->where(
                    'status',
                    'present'
                )
                ->count();

        $late =
            collect($employeeAttendance)
                ->where(
                    'status',
                    'late'
                )
                ->count();

        $absent =
            collect($employeeAttendance)
                ->where(
                    'status',
                    'absent'
                )
                ->count();

        $checkedOut =
            collect($employeeAttendance)
                ->filter(
                    fn ($item) =>
                        !empty($item['check_out'])
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Attendance Rate
        |--------------------------------------------------------------------------
        */

        $attendanceRate = 0;

        if ($totalEmployees > 0) {

            $attendanceRate =
                round(
                    (($present + $late) /
                        $totalEmployees) * 100,
                    2
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Return Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'report' => [

                'type' => $type,

                'title' =>
                    match ($type) {
                        'daily' =>
                            'Daily Attendance Report',

                        'weekly' =>
                            'Weekly Attendance Report',

                        'monthly' =>
                            'Monthly Attendance Report',

                        default =>
                            'Attendance Report',
                    },

                'from' =>
                    $from->format(
                        'Y-m-d'
                    ),

                'to' =>
                    $to->format(
                        'Y-m-d'
                    ),

                'generated_at' =>
                    now()->format(
                        'Y-m-d H:i:s'
                    ),
            ],

            'summary' => [

                'total_employees' =>
                    $totalEmployees,

                'present' =>
                    $present,

                'late' =>
                    $late,

                'absent' =>
                    $absent,

                'checked_out' =>
                    $checkedOut,

                'attendance_rate' =>
                    $attendanceRate,
            ],

            'data' =>
                $employeeAttendance,
        ]);
    }
}

