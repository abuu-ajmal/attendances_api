<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeService
{

    public function employeeIds(User $user)
{
    $query = Employee::query()->select('id');

    $this->applyEmployeeScope(
        $query,
        $user
    );

    return $query;
}
    public function applyEmployeeScope(
        Builder $query,
        User $user
    ): Builder {

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if (
            $user->hasRole('director') ||
            $user->hasRole('secretary')
        ) {
            return $query;
        }

        $scopes = $user->scopes;

        if ($scopes->isEmpty()) {
            return $query->where('id', $user->employee_id);
        }

        $query->where(function ($q) use ($scopes, $user) {

            foreach ($scopes as $scope) {

                if ($scope->scope_type === 'organization') {
                    return;
                }

                if ($scope->scope_type === 'department') {
                    $q->orWhere(
                        'department_id',
                        $scope->scope_id
                    );
                }

                if ($scope->scope_type === 'unit') {
                    $q->orWhere(
                        'unit_id',
                        $scope->scope_id
                    );
                }
            }

            $q->orWhere('id', $user->employee_id);
        });

        return $query;
    }

    public function canViewEmployee(
        User $user,
        Employee $employee
    ): bool {

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if (
            $user->hasRole('director') ||
            $user->hasRole('secretary')
        ) {
            return true;
        }

        if ($user->employee_id === $employee->id) {
            return true;
        }

        foreach ($user->scopes as $scope) {

            if ($scope->scope_type === 'organization') {
                return true;
            }

            if (
                $scope->scope_type === 'department' &&
                $employee->department_id === $scope->scope_id
            ) {
                return true;
            }

            if (
                $scope->scope_type === 'unit' &&
                $employee->unit_id === $scope->scope_id
            ) {
                return true;
            }
        }

        return false;
    }
}