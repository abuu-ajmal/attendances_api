<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceWarningService
{
    /**
     * Time ya kuanza kazi.
     */
    private const WORK_START_HOUR = 8;

    private const WORK_START_MINUTE = 0;

    /**
     * Idadi ya siku za late zinazohitajika
     * ili warning ionekane.
     */
    private const WARNING_THRESHOLD = 3;

    /**
     * Timezone ya Zanzibar/Tanzania.
     */
    private const TIMEZONE = 'Africa/Dar_es_Salaam';

    /**
     * Get attendance warning ya employee.
     */
    public function getWarning(Employee $employee): array
    {
        $timezone = self::TIMEZONE;

        $today = now($timezone)->startOfDay();

        /*
         * Tunachukua attendance za siku 90 zilizopita.
         * Hii inatosha kutafuta streak ya siku 3.
         */
        $records = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->where('type', 'check_in')
            ->whereBetween('occurred_at', [
                $today->copy()->subDays(90)->startOfDay(),
                $today->copy()->endOfDay(),
            ])
            ->orderBy('occurred_at')
            ->get([
                'id',
                'occurred_at',
            ]);

        /*
         * Tunahifadhi check-in moja tu kwa kila tarehe.
         *
         * Ikiwa kuna records zaidi ya moja siku moja,
         * tunatumia check-in ya kwanza.
         */
        $checkInsByDate = [];

        foreach ($records as $record) {

            $checkIn = Carbon::parse(
                $record->occurred_at,
                $timezone
            )->setTimezone($timezone);

            $date = $checkIn->toDateString();

            if (
                !isset($checkInsByDate[$date]) ||
                $checkIn->lt($checkInsByDate[$date]['datetime'])
            ) {
                $checkInsByDate[$date] = [
                    'date' => $date,
                    'datetime' => $checkIn,
                ];
            }
        }

        /*
         * Tafuta siku ambazo employee alikuwa late.
         */
        $lateByDate = [];

        foreach ($checkInsByDate as $date => $attendance) {

            /** @var Carbon $checkIn */
            $checkIn = $attendance['datetime'];

            $workStart = $checkIn->copy()
                ->startOfDay()
                ->setTime(
                    self::WORK_START_HOUR,
                    self::WORK_START_MINUTE,
                    0
                );

            if ($checkIn->gt($workStart)) {

                $minutesLate = $workStart->diffInMinutes(
                    $checkIn
                );

                $lateByDate[$date] = [
                    'date' => $date,
                    'check_in' => $checkIn->format('H:i'),
                    'minutes_late' => $minutesLate,
                    'datetime' => $checkIn,
                ];
            }
        }

        /*
         * Determine siku ya kuanzia kuangalia streak.
         *
         * Kama leo ame-check-in:
         *   tunaanzia leo.
         *
         * Kama leo hajacheck-in:
         *   hatumhesabu leo kama absent/late;
         *   tunaanzia siku ya mwisho ya kazi iliyopita.
         */
        $anchor = $today->copy();

        if (
            !isset(
                $checkInsByDate[
                    $today->toDateString()
                ]
            )
        ) {
            $anchor->subDay();
        }

        /*
         * Weekend hazihesabiwi kama working days.
         */
        $anchor = $this->previousWorkingDayOrSame(
            $anchor
        );

        $consecutiveLate = [];

        /*
         * Tafuta late days zinazofuatana.
         */
        while (true) {

            $date = $anchor->toDateString();

            /*
             * Kama hakuna check-in siku hiyo,
             * streak inakatika.
             */
            if (!isset($checkInsByDate[$date])) {
                break;
            }

            /*
             * Kama alikuwepo lakini hakuwa late,
             * streak inakatika.
             */
            if (!isset($lateByDate[$date])) {
                break;
            }

            array_unshift(
                $consecutiveLate,
                $lateByDate[$date]
            );

            /*
             * Tukifikisha threshold,
             * hatuhitaji kuendelea zaidi.
             */
            if (
                count($consecutiveLate)
                >= self::WARNING_THRESHOLD
            ) {
                break;
            }

            /*
             * Rudi kwenye working day iliyopita.
             */
            $anchor->subDay();

            $anchor = $this->previousWorkingDayOrSame(
                $anchor
            );
        }

        /*
         * Ondoa Carbon object kabla ya kurudisha JSON.
         */
        $lateRecords = array_map(
            function (array $record) {
                return [
                    'date' => $record['date'],
                    'check_in' => $record['check_in'],
                    'minutes_late' => $record['minutes_late'],
                ];
            },
            $consecutiveLate
        );

        $hasWarning =
            count($consecutiveLate)
            >= self::WARNING_THRESHOLD;

        /*
         * Employee information.
         */
        $employee->loadMissing([
            'department',
            'unit',
        ]);

        return [
            'has_warning' => $hasWarning,

            'threshold' => self::WARNING_THRESHOLD,

            'consecutive_late_days' => count(
                $consecutiveLate
            ),

            'required_check_in' => sprintf(
                '%02d:%02d',
                self::WORK_START_HOUR,
                self::WORK_START_MINUTE
            ),

            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'employee_no' => $employee->employee_no,
                'job_title' => $employee->job_title,

                'department' => optional(
                    $employee->department
                )->name,

                'unit' => optional(
                    $employee->unit
                )->name,
            ],

            'late_records' => $lateRecords,

            'message' => $hasWarning
                ? 'You have reported late for three consecutive working days.'
                : null,
        ];
    }

    /**
     * Rudisha siku ya kazi.
     *
     * Saturday = 6
     * Sunday   = 0
     */
    private function previousWorkingDayOrSame(
        Carbon $date
    ): Carbon {

        while ($date->isWeekend()) {
            $date->subDay();
        }

        return $date;
    }
}