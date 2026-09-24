<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AttendanceService
{
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
    ): AttendanceRecord {

        $employee = $user->employee;

        if (!$employee) {
            throw new \Exception(
                'User is not linked to an employee.'
            );
        }

        if ($employee->employment_status !== 'active') {
            throw new \Exception(
                'Employee is not active.'
            );
        }

        $uuid = $uuid ?: (string) Str::uuid();

        /*
        |--------------------------------------------------------------------------
        | Idempotency
        |--------------------------------------------------------------------------
        */

        $existing = AttendanceRecord::where(
            'uuid',
            $uuid
        )->first();

        if ($existing) {
            return $existing;
        }

        $date = Carbon::parse($occurredAt)
            ->timezone('Africa/Dar_es_Salaam')
            ->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Check duplicate attendance
        |--------------------------------------------------------------------------
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
        | Check-in / Check-out sequence
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
        | Photo
        |--------------------------------------------------------------------------
        */

        $photoPath = null;

        if ($photo) {

            $photoPath = $photo->store(
                'attendance/photos',
                'local'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        return DB::transaction(function () use (
            $uuid,
            $employee,
            $type,
            $occurredAt,
            $latitude,
            $longitude,
            $accuracy,
            $photoPath,
            $deviceId,
            $remarks
        ) {

            return AttendanceRecord::create([
                'uuid' => $uuid,
                'employee_id' => $employee->id,
                'type' => $type,
                'occurred_at' => Carbon::parse(
                    $occurredAt
                )->timezone('Africa/Dar_es_Salaam'),

                'server_received_at' => now(
                    'Africa/Dar_es_Salaam'
                ),

                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,

                'photo_path' => $photoPath,

                'device_id' => $deviceId,

                'sync_status' => 'synced',

                'remarks' => $remarks,
            ]);
        });
    }
}