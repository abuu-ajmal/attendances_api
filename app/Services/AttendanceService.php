<?php
namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendanceService
{
    /**
     * Record attendance.
     *
     * Used by both:
     * - Online attendance
     * - Offline attendance synchronized later
     */
    public function record(
        User $user,
        string $type,
        string $occurredAt,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracy = null,
        ?UploadedFile $photo = null,
        ?string $deviceId = null,
        ?string $remarks = null,
        ?string $uuid = null
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Find employee
        |--------------------------------------------------------------------------
        */

        $employee = $user->employee;

        if (!$employee) {
            throw new \Exception(
                'User is not linked to an employee.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Employee status
        |--------------------------------------------------------------------------
        */

        if ($employee->employment_status !== 'active') {
            throw new \Exception(
                'Employee is not active.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate attendance type
        |--------------------------------------------------------------------------
        */

        if (!in_array($type, ['check_in', 'check_out'], true)) {
            throw new \Exception(
                'Invalid attendance type.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | UUID
        |--------------------------------------------------------------------------
        |
        | Flutter creates this UUID when the attendance is created.
        | Laravel uses it for idempotency.
        |
        */

        $uuid = $uuid ?: (string) Str::uuid();

        /*
        |--------------------------------------------------------------------------
        | Idempotency check
        |--------------------------------------------------------------------------
        |
        | If Flutter retries the same offline record, we don't create
        | another database record.
        |
        */

        $existing = AttendanceRecord::query()
            ->where('uuid', $uuid)
            ->first();

        if ($existing) {
            return [
                'attendance' => $existing->load('employee'),
                'created' => false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Convert occurrence time to Tanzania timezone
        |--------------------------------------------------------------------------
        */

        $occurredAtTz = Carbon::parse($occurredAt)
            ->timezone('Africa/Dar_es_Salaam');

        $date = $occurredAtTz->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Duplicate check
        |--------------------------------------------------------------------------
        |
        | One check-in and one check-out per employee per day.
        |
        */

        $alreadyRecorded = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('occurred_at', $date)
            ->where('type', $type)
            ->exists();

        if ($alreadyRecorded) {
            throw new \Exception(
                "Employee already has a {$type} record for this date."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Check-out requires check-in
        |--------------------------------------------------------------------------
        */

        if ($type === 'check_out') {

            $hasCheckIn = AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereDate('occurred_at', $date)
                ->where('type', 'check_in')
                ->exists();

            if (!$hasCheckIn) {
                throw new \Exception(
                    'Employee must check in before checking out.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Database transaction
        |--------------------------------------------------------------------------
        */

        $result = DB::transaction(function () use (
            $uuid,
            $employee,
            $type,
            $occurredAtTz,
            $latitude,
            $longitude,
            $accuracy,
            $photo,
            $deviceId,
            $remarks
        ) {

            /*
            |--------------------------------------------------------------------------
            | Check UUID again inside transaction
            |--------------------------------------------------------------------------
            */

            $existing = AttendanceRecord::query()
                ->where('uuid', $uuid)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return [
                    'attendance' => $existing,
                    'created' => false,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Store photo
            |--------------------------------------------------------------------------
            */

            $photoPath = null;

            if ($photo) {

                $photoPath = $photo->store(
                    'attendances/photos',
                    'public'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Create attendance
            |--------------------------------------------------------------------------
            */

            $attendance = AttendanceRecord::create([

                'uuid' => $uuid,

                'employee_id' => $employee->id,

                'type' => $type,

                'occurred_at' => $occurredAtTz,

                'server_received_at' => now(
                    'Africa/Dar_es_Salaam'
                ),

                'latitude' => $latitude,

                'longitude' => $longitude,

                'accuracy' => $accuracy,

                'photo_path' => $photoPath,

                'device_id' => $deviceId,

                /*
                |--------------------------------------------------------------------------
                | Important
                |--------------------------------------------------------------------------
                |
                | This record has reached Laravel successfully.
                |
                */

                'sync_status' => 'synced',

                'remarks' => $remarks,
            ]);

            return [
                'attendance' => $attendance,
                'created' => true,
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'attendance' => $result['attendance']->load('employee'),
            'created' => $result['created'],
        ];
    }
}