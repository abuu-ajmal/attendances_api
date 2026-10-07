<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\AttendanceWarningController;

Route::prefix('auth')->group(function () {

    Route::post(
        '/login',
        [AuthController::class, 'login']
    );

    Route::middleware('auth:sanctum')->group(function () {

        Route::get(
            '/me',
            [AuthController::class, 'me']
        );

        Route::post(
            '/logout',
            [AuthController::class, 'logout']
        );
    });
});


Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )->middleware(
        'permission:view_dashboard'
    );


    /*
    |--------------------------------------------------------------------------
    | Employees
    |--------------------------------------------------------------------------
    */

    // Route::get(
    //     '/employees',
    //     [EmployeeController::class, 'index']
    // )->middleware(
    //     'permission:view_employees'
    // );

    // Route::get(
    //     '/employees/{employee}',
    //     [EmployeeController::class, 'show']
    // )->middleware(
    //     'permission:view_employees'
    // );

       Route::get(
        '/employees',
        [EmployeeController::class, 'index']
    );

    Route::post(
        '/employees',
        [EmployeeController::class, 'store']
    );

    Route::get(
        '/employees/{employee}',
        [EmployeeController::class, 'show']
    );

    Route::put(
        '/employees/{employee}',
        [EmployeeController::class, 'update']
    );

    Route::delete(
        '/employees/{employee}',
        [EmployeeController::class, 'destroy']
    );


    /*
    |--------------------------------------------------------------------------
    | Departments
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/departments',
        [DepartmentController::class, 'index']
    )->middleware(
        'permission:view_departments'
    );

    Route::post(
        '/departments',
        [DepartmentController::class, 'store']
    )->middleware(
        'permission:manage_departments'
    );

    Route::get(
        '/departments/{department}',
        [DepartmentController::class, 'show']
    )->middleware(
        'permission:view_departments'
    );

    Route::put(
        '/departments/{department}',
        [DepartmentController::class, 'update']
    )->middleware(
        'permission:manage_departments'
    );

    Route::delete(
        '/departments/{department}',
        [DepartmentController::class, 'destroy']
    )->middleware(
        'permission:manage_departments'
    );


    /*
    |--------------------------------------------------------------------------
    | Units
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/units',
        [UnitController::class, 'index']
    )->middleware(
        'permission:view_units'
    );

    Route::post(
        '/units',
        [UnitController::class, 'store']
    )->middleware(
        'permission:manage_units'
    );

    Route::get(
        '/units/{unit}',
        [UnitController::class, 'show']
    )->middleware(
        'permission:view_units'
    );

    Route::put(
        '/units/{unit}',
        [UnitController::class, 'update']
    )->middleware(
        'permission:manage_units'
    );

    Route::delete(
        '/units/{unit}',
        [UnitController::class, 'destroy']
    )->middleware(
        'permission:manage_units'
    );


    /*
    |--------------------------------------------------------------------------
    | Attendance
    |--------------------------------------------------------------------------
    */

    

     Route::get(
        '/attendance/my/warning',
        [AttendanceController::class, 'myWarning']
    );

      Route::get(
        '/attendance/my/warning-letter',
        [AttendanceWarningController::class, 'myWarningLetter']
    );

    Route::post(
        '/attendance',
        [AttendanceController::class, 'store']
    )->middleware(
        'permission:record_attendance'
    );

    Route::get(
        '/attendance/my',
        [AttendanceController::class, 'myAttendance']
    )->middleware(
        'permission:view_attendance'
    );

    Route::get(
        '/attendance',
        [AttendanceController::class, 'index']
    )->middleware(
        'permission:view_attendance'
    );


    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/reports/attendance',
        [ReportController::class, 'attendance']
    )->middleware(
        'permission:view_reports'
    );


    /*
    |--------------------------------------------------------------------------
    | Devices
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/devices',
        [DeviceController::class, 'index']
    )->middleware(
        'permission:manage_devices'
    );

    Route::post(
        '/devices',
        [DeviceController::class, 'store']
    );

    Route::delete(
        '/devices/{device}',
        [DeviceController::class, 'destroy']
    )->middleware(
        'permission:manage_devices'
    );
});