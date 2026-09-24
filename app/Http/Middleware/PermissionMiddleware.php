<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'User account is inactive.'
            ], 403);
        }

        if (!$user->hasPermission($permission)) {
            return response()->json([
                'message' => 'You do not have permission to perform this action.'
            ], 403);
        }

        return $next($request);
    }
}