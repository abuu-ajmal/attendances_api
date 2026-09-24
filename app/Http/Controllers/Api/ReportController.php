<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Services\ScopeService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct(
        protected ScopeService $scopeService
    ) {}

    public function attendance(Request $request)
    {
        $request->validate([
            'from' => [
                'required',
                'date',
            ],

            'to' => [
                'required',
                'date',
                'after_or_equal:from',
            ],
        ]);

        $employeeIds =
            $this->scopeService
                ->employeeIds(
                    $request->user()
                );

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
                    Carbon::parse(
                        $request->from
                    )->startOfDay(),

                    Carbon::parse(
                        $request->to
                    )->endOfDay(),
                ]
            )
            ->orderBy('occurred_at')
            ->get();

        return response()->json([
            'success' => true,

            'filters' => [
                'from' => $request->from,
                'to' => $request->to,
            ],

            'data' => $records,
        ]);
    }
}