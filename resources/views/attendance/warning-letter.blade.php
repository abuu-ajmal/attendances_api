<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Attendance Warning Letter</title>

    <style>
        @page {
            margin: 40px 50px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #222;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .logo {
            width: 75px;
            height: 75px;
            margin-bottom: 8px;
        }

        .ministry {
            font-size: 18px;
            font-weight: bold;
        }

        .country {
            font-size: 15px;
            font-weight: bold;
        }

        .system {
            font-size: 10px;
            margin-top: 5px;
        }

        .meta {
            width: 100%;
            margin-bottom: 25px;
        }

        .meta table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            padding: 3px 0;
        }

        .right {
            text-align: right;
        }

        .recipient {
            margin-bottom: 20px;
        }

        .bold {
            font-weight: bold;
        }

        .subject {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            text-decoration: underline;
            margin: 25px 0;
        }

        .paragraph {
            text-align: justify;
            margin-bottom: 15px;
        }

        .records {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .records th {
            background: #eeeeee;
            border: 1px solid #777;
            padding: 8px;
            text-align: left;
        }

        .records td {
            border: 1px solid #999;
            padding: 8px;
        }

        .reporting-time {
            border: 1px solid #999;
            background: #f7f7f7;
            padding: 12px;
            margin: 20px 0;
        }

        .signature {
            margin-top: 50px;
        }

        .signature-line {
            width: 230px;
            border-bottom: 1px solid #222;
            margin-top: 40px;
        }

        .footer {
            border-top: 1px solid #999;
            margin-top: 50px;
            padding-top: 8px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>

<body>

<div class="header">

    @php
        $logoPath = public_path('images/moh_logo.png');
    @endphp

    @if (file_exists($logoPath))
        <img
            src="{{ $logoPath }}"
            class="logo"
            alt="Ministry of Health Zanzibar"
        >
    @endif

    <div class="ministry">
        MINISTRY OF HEALTH
    </div>

    <div class="country">
        ZANZIBAR
    </div>

    <div class="system">
        STAFF ATTENDANCE MANAGEMENT SYSTEM
    </div>

</div>


<div class="meta">

    <table>

        <tr>

            <td>
                <span class="bold">REF:</span>
                {{ $reference ?? '-' }}
            </td>

            <td class="right">

                <span class="bold">Date:</span>

                @if(isset($date))
                    {{ $date->format('d F Y') }}
                @else
                    -
                @endif

            </td>

        </tr>

    </table>

</div>


<div class="recipient">

    <div class="bold">
        TO:
    </div>

   <div>
    <span class="bold">Name:</span>
    {{ $warning['employee']['name'] ?? 'Employee' }}
</div>

    <div>
        <span class="bold">
            Employee No:
        </span>

        {{ $warning['employee']['employee_no'] ?? '-' }}
    </div>

    <div>
        <span class="bold">
            Department:
        </span>

        {{ $warning['employee']['department'] ?? '-' }}
    </div>

    <div>
        <span class="bold">
            Unit:
        </span>

        {{ $warning['employee']['unit'] ?? '-' }}
    </div>

</div>


<div class="subject">
    SUBJECT: FORMAL WARNING FOR REPEATED LATENESS
</div>


<div>
    Dear
    {{ $warning['employee']['name'] ?? 'Employee' }},
</div>


<div class="paragraph">

    This letter serves as a formal warning regarding
    your repeated lateness to work.

</div>


<div class="paragraph">

    According to the attendance records maintained by
    the Staff Attendance Management System, you reported
    to work late for

    <span class="bold">

        {{ $warning['consecutive_late_days'] ?? 0 }}
        consecutive working days

    </span>

    as follows:

</div>


<table class="records">

    <thead>

        <tr>

            <th style="width: 10%;">
                No.
            </th>

            <th style="width: 35%;">
                Date
            </th>

            <th style="width: 25%;">
                Check-in
            </th>

            <th style="width: 30%;">
                Minutes Late
            </th>

        </tr>

    </thead>

    <tbody>

        @forelse(
            $warning['late_records'] ?? []
            as $index => $record
        )

            @php

                try {
                    $recordDate =
                        \Carbon\Carbon::parse(
                            $record['date']
                        );
                } catch (\Throwable $e) {
                    $recordDate = null;
                }

            @endphp

            <tr>

                <td>
                    {{ $index + 1 }}
                </td>

                <td>

                    @if($recordDate)

                        {{ $recordDate->format('d F Y') }}

                    @else

                        {{ $record['date'] ?? '-' }}

                    @endif

                </td>

                <td>
                    {{ $record['check_in'] ?? '--:--' }}
                </td>

                <td>
                    {{ $record['minutes_late'] ?? 0 }}
                    minutes
                </td>

            </tr>

        @empty

            <tr>

                <td
                    colspan="4"
                    style="text-align: center;"
                >
                    No late attendance records found.
                </td>

            </tr>

        @endforelse

    </tbody>

</table>


<div class="reporting-time">

    The official reporting time is

    <span class="bold">

        {{ $warning['required_check_in'] ?? '08:00' }}

    </span>.

</div>


<div class="paragraph">

    You are therefore required to observe the official
    working hours and ensure that you report to work
    on time.

</div>


<div class="paragraph">

    You are strongly advised to correct this behaviour
    with immediate effect. Continued lateness may result
    in further administrative action in accordance with
    the applicable employment regulations and
    organizational procedures.

</div>


<div class="signature">

    <div>
        Yours faithfully,
    </div>

    <div class="signature-line"></div>

    <div style="margin-top: 8px;">
        <span class="bold">
            Human Resources / Authorized Officer
        </span>
    </div>

    <div>
        Ministry of Health
    </div>

    <div>
        Zanzibar
    </div>

</div>


<div class="footer">

    This document was generated from the
    Staff Attendance Management System.

    <br>

    Ministry of Health — Zanzibar

</div>

</body>

</html>