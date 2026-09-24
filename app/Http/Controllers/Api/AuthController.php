<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => [
                'required',
                'string',
            ],

            'password' => [
                'required',
                'string',
            ],

            'device_name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $login = $validated['login'];

        $user = User::query()
            ->with([
                'employee.department',
                'employee.unit',
                'roles.permissions',
            ])
            ->where(function ($query) use ($login) {

                $query
                    ->where('email', $login)
                    ->orWhereHas(
                        'employee',
                        function ($q) use ($login) {
                            $q->where(
                                'employee_no',
                                $login
                            );
                        }
                    );
            })
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Check credentials
        |--------------------------------------------------------------------------
        */

        if (
            !$user ||
            !Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid login credentials.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Check account status
        |--------------------------------------------------------------------------
        */

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Token abilities
        |--------------------------------------------------------------------------
        */

        $abilities = $user
            ->permissions()
            ->pluck('name')
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Create Sanctum token
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken(
            $validated['device_name'] ?? 'SAMS Mobile',
            $abilities
        )->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user()->load([
            'employee.department',
            'employee.unit',
            'roles.permissions',
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request
            ->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}