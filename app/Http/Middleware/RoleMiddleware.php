<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        // Check for Bearer token
        $bearerToken = $request->bearerToken();
        if (!$bearerToken) {
            Log::error('No bearer token provided');
            return response()->json([
                'status' => 'error',
                'message' => 'No bearer token provided'
            ], 401);
        }

        $user = $request->user();
        if (!$user) {
            Log::error('Invalid bearer token', ['token' => $bearerToken]);
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid bearer token'
            ], 401);
        }

        // Load roles directly to avoid N+1
        $user->load('roles');
        $userRoles = $user->roles->pluck('name')->toArray();

        Log::info('Role Check', [
            'user_id' => $user->id,
            'token' => substr($bearerToken, 0, 10) . '...',
            'user_roles' => $userRoles,
            'required_role' => $role
        ]);

        if (!in_array($role, $userRoles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient permissions',
                'required_role' => $role
            ], 403);
        }

        return $next($request);
    }
}
