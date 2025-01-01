<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Laravel\Sanctum\PersonalAccessToken;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $roles)
    {
        $token = PersonalAccessToken::findToken($request->bearerToken());

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or missing token'
            ], 401);
        }

        $user = $token->tokenable;
        $allowedRoles = explode(',', $roles); // Convert "admin,cashier" to array

    

        // Check if user has ANY of the allowed roles
        $hasRole = $user->roles()->whereIn('name', $allowedRoles)->exists();

        if (!$hasRole) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - Required roles: ' . $roles
            ], 403);
        }

        $request->setUserResolver(fn () => $user);
        return $next($request);
    }
}
