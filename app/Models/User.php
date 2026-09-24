<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];


    /*
    |--------------------------------------------------------------------------
    | Employee
    |--------------------------------------------------------------------------
    */

    public function employee()
    {
        return $this->belongsTo(
            Employee::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'role_user'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Has Role
    |--------------------------------------------------------------------------
    */

    public function hasRole(
        string $roleName
    ): bool {
        return $this->roles()
            ->where(
                'name',
                $roleName
            )
            ->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    public function permissions()
    {
        return Permission::query()
            ->whereHas(
                'roles',
                function ($query) {
                    $query->whereIn(
                        'roles.id',
                        $this->roles()
                            ->pluck('roles.id')
                    );
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Has Permission
    |--------------------------------------------------------------------------
    */

    public function hasPermission(
        string $permissionName
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        */

        if (
            $this->hasRole(
                'super_admin'
            )
        ) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Normal permission
        |--------------------------------------------------------------------------
        */

        return $this->permissions()
            ->where(
                'name',
                $permissionName
            )
            ->exists();
    }
}