<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Attendance Warning Letter</title>

    <style>
        @page {
            margin: 35px 50px 45px 50px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #222;
            font-size: 12px;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .letter {
            width: 100%;
        }

        /* =========================
           HEADER
        ========================= */

        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .logo {
            width: 75px;
            height: 75px;
            margin-bottom: 5px;
        }

        .ministry-name {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .country {
            font-size: 15px;
            font-weight: bold;
            margin-top: 2px;
        }

        .subtitle {
            font-size: 10px;
            margin-top: 3px;
        }

        /* =========================
           DOCUMENT META
        ========================= */

        .document-meta {
            width: 100%;
            margin-bottom: 20px;
        }

        .document-meta table {
            width: 100%;
            border-collapse: collapse;
        }

        .document-meta td {
            vertical-align: top;
            padding: 2px 0;
        }

        .meta-right {
            text-align: right;
        }

        /* =========================
           RECIPIENT
        ========================= */

        .recipient {
            margin-top: 8px;
            margin-bottom: 20px;
        }

        .recipient-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* =========================
           SUBJECT
        ========================= */

        .subject {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0;
        }

        /* =========================
           CONTENT
        ========================= */

        .salutation {
            margin-bottom: 15px;
        }

        .paragraph {
            text-align: justify;
            margin-bottom: 13px;
        }

        /* =========================
           ATTENDANCE TABLE
        ========================= */

        .records {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 18px 0;
        }

        .records th {
            background: #eeeeee;
            border: 1px solid #777;
            padding: 7px;
            text-align: left;
            font-weight: bold;
        }

        .records td {
            border: 1px solid #999;
            padding: 7px;
        }

        /* =========================
           REPORTING TIME
        ========================= */

        .reporting-time {
            margin: 18px 0;
            padding: 10px 12px;
            border: 1px solid #999;
            background: #f7f7f7;
        }

        /* =========================
           SIGNATURE
        ========================= */

        .signature {
            margin-top: 45px;
        }

        .signature-line {
            margin-top: 35px;
            width: 230px;
            border-bottom: 1px solid #222;
        }

        /* =========================
           FOOTER
        ========================= */

        .footer {
            margin-top: 40px;
            padding-top: 8px;
            border-top: 1px solid #999;
            text-align: center;
            font-size: 9px;
            color: #666;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="letter">

    {{-- =========================
         HEADER
    ========================== --}}

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

        <div class="ministry-name">
            MINISTRY OF HEALTH
        </div>

        <div class="country">
            ZANZIBAR
        </div>

        <div class="subtitle">
            STAFF ATTENDANCE MANAGEMENT SYSTEM
        </div>

    </div>


    {{-- =========================
         REFERENCE + DATE
    ========================== --}}

    <div class="document-meta">

        <table>

            <tr>

                <td>
                    <span class="bold">
                        REF:
                    </span>

                    {{ $reference ?? '-' }}
                </td>

                <td class="meta-right">

                    <span class="bold">
                        Date:
                    </span>

                    @if(isset($date))
                        {{ $date->format('d F Y') }}
                    @else
                        -
                    @endif

                </td>

            </tr>

        </table>

    </div>


    {{-- =========================
         RECIPIENT
    ========================== --}}

   <div class="recipient">

    <div class="bold">
        TO:
    </div>

    <div>
        <span class="bold">
            Name:
        </span>

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


    {{-- =========================
         SUBJECT
    ========================== --}}

    <div class="subject">

        SUBJECT: FORMAL WARNING FOR REPEATED LATENESS

    </div>


    {{-- =========================
         SALUTATION
    ========================== --}}

    <div class="salutation">

        Dear Mr./Ms.
        {{ $warning['employee']['name'] ?? 'Employee' }},

    </div>


    {{-- =========================
         INTRODUCTION
    ========================== --}}

    <div class="paragraph">

        This letter serves as a formal warning regarding
        your repeated lateness to work.

    </div>


    {{-- =========================
         ATTENDANCE EXPLANATION
    ========================== --}}

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


    {{-- =========================
         ATTENDANCE RECORDS
    ========================== --}}

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

                    $checkIn =
                        $record['check_in'] ?? '--:--';

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
                        {{ $checkIn }}
                    </td>

                    <td>

                        {{ $record['minutes_late'] ?? 0 }}
                        minutes

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="4" style="text-align: center;">
                        No late attendance records found.
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>


    {{-- =========================
         REPORTING TIME
    ========================== --}}

    <div class="reporting-time">

        The official reporting time is

        <span class="bold">

            {{ $warning['required_check_in'] ?? '08:00' }}

        </span>.

    </div>


    {{-- =========================
         REQUIREMENT
    ========================== --}}

    <div class="paragraph">

        You are therefore required to observe the official
        working hours and ensure that you report to work
        on time.

    </div>


    {{-- =========================
         ADMINISTRATIVE NOTICE
    ========================== --}}

    <div class="paragraph">

        You are strongly advised to correct this behaviour
        with immediate effect. Continued lateness may result
        in further administrative action in accordance with
        the applicable employment regulations and
        organizational procedures.

    </div>


    {{-- =========================
         SIGNATURE
    ========================== --}}

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


    {{-- =========================
         FOOTER
    ========================== --}}

    <div class="footer">

        This document was generated from the
        Staff Attendance Management System.

        <br>

        Ministry of Health — Zanzibar

    </div>

</div>

</body>

</html>