<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_no',
        'first_name',
        'middle_name',
        'last_name',
        'phone',
        'email',
        'gender',
        'date_of_birth',
        'department_id',
        'unit_id',
        'job_title',
        'supervisor_id',
        'profile_photo',
        'employment_status',
    ];

    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
    ];


    /*
    |--------------------------------------------------------------------------
    | Department
    |--------------------------------------------------------------------------
    */

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Unit
    |--------------------------------------------------------------------------
    */

    public function unit(): BelongsTo
    {
        return $this->belongsTo(
            Unit::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Supervisor
    |--------------------------------------------------------------------------
    */

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'supervisor_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Subordinates
    |--------------------------------------------------------------------------
    */

    public function subordinates(): HasMany
    {
        return $this->hasMany(
            Employee::class,
            'supervisor_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | User Account
    |--------------------------------------------------------------------------
    */

    public function user(): HasOne
    {
        return $this->hasOne(
            User::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Attendance Records
    |--------------------------------------------------------------------------
    */

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(
            Attendance::class
        );
    }
}