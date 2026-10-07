<?php
namespace App\Services;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeService
{
    /**
     * Get employee IDs visible to the authenticated user.
     */
    public function employeeIds(User $user)
    {
        $query = Employee::query()->select('id');

        $this->applyEmployeeScope($query, $user);

        return $query;
    }

    /**
     * Apply employee visibility scope.
     */
    public function applyEmployeeScope(
        Builder $query,
        User $user
    ): Builder {

        // Super Admin can see everyone.
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // Director and Secretary can see everyone.
        if (
            $user->hasRole('director') ||
            $user->hasRole('secretary')
        ) {
            return $query;
        }

        /*
         * Make sure scopes is always treated as a collection.
         *
         * If the relationship returns null for any reason,
         * collect() will give us an empty collection instead
         * of causing:
         *
         * Call to a member function isEmpty() on null
         */
        $scopes = collect($user->scopes);

        /*
         * If the user has no scope, allow the user to see
         * only their own employee record.
         */
        if ($scopes->isEmpty()) {

            if (!empty($user->employee_id)) {
                return $query->where(
                    'id',
                    $user->employee_id
                );
            }

            // User has no employee and no scope.
            // Return no employees.
            return $query->whereRaw('1 = 0');
        }

        /*
         * Check whether the user has organization-level access.
         *
         * Organization scope means the user can see all employees.
         */
        $hasOrganizationScope = $scopes->contains(function ($scope) {
            return $scope->scope_type === 'organization';
        });

        if ($hasOrganizationScope) {
            return $query;
        }

        /*
         * Department, Unit and own employee access.
         */
        $query->where(function ($q) use ($scopes, $user) {

            foreach ($scopes as $scope) {

                if (
                    $scope->scope_type === 'department' &&
                    !empty($scope->scope_id)
                ) {
                    $q->orWhere(
                        'department_id',
                        $scope->scope_id
                    );
                }

                if (
                    $scope->scope_type === 'unit' &&
                    !empty($scope->scope_id)
                ) {
                    $q->orWhere(
                        'unit_id',
                        $scope->scope_id
                    );
                }
            }

            /*
             * Always allow the user to see their own employee
             * record when employee_id exists.
             */
            if (!empty($user->employee_id)) {
                $q->orWhere(
                    'id',
                    $user->employee_id
                );
            }
        });

        return $query;
    }

    /**
     * Check whether a user can view a specific employee.
     */
    public function canViewEmployee(
        User $user,
        Employee $employee
    ): bool {

        // Super Admin can view everyone.
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Director and Secretary can view everyone.
        if (
            $user->hasRole('director') ||
            $user->hasRole('secretary')
        ) {
            return true;
        }

        // User can always view their own employee record.
        if (
            !empty($user->employee_id) &&
            $user->employee_id === $employee->id
        ) {
            return true;
        }

        /*
         * Always convert scopes to a collection so that
         * foreach never receives null.
         */
        $scopes = collect($user->scopes);

        foreach ($scopes as $scope) {

            // Organization-level access.
            if ($scope->scope_type === 'organization') {
                return true;
            }

            // Department-level access.
            if (
                $scope->scope_type === 'department' &&
                $employee->department_id === $scope->scope_id
            ) {
                return true;
            }

            // Unit-level access.
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
