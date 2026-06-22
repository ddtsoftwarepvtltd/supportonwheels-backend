<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): mixed
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        // Super admin sab kuch access kar sakta hai
        if ($user->user_type === 'super_admin') {
            return $next($request);
        }

        foreach ($roles as $role) {
            if ($user->user_type === $role || $user->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Aapke paas is resource ka access nahi hai.',
        ], 403);
    }
}
