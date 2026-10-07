<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AttendanceWarningService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceWarningController extends Controller
{
    public function __construct(
        protected AttendanceWarningService $attendanceWarningService
    ) {
    }

    public function myWarningLetter(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticated user not found.',
                ], 401);
            }

            $employee = $user->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found.',
                ], 404);
            }

            $warning = $this->attendanceWarningService
                ->getWarning($employee);

            if (!($warning['has_warning'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'You currently do not have an attendance warning letter.',
                ], 404);
            }

            $date = now('Africa/Dar_es_Salaam');

            $reference = 'MOH/HR/ATT/'
                . $date->format('Y')
                . '/'
                . str_pad(
                    (string) $employee->id,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

            $pdf = Pdf::loadView(
                'attendance.warning-letter',
                [
                    'warning' => $warning,
                    'employee' => $employee,
                    'reference' => $reference,
                    'date' => $date,
                ]
            );

            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream(
                'attendance-warning-letter.pdf'
            );

        } catch (\Throwable $e) {

            Log::error(
                'Attendance warning letter generation failed.',
                [
                    'user_id' => $request->user()?->id,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to generate warning letter.',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}