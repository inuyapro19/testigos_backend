<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // If no permissions specified, just check authentication
        if (empty($permissions)) {
            return $next($request);
        }

        // Check if user has ALL required permissions
        foreach ($permissions as $permission) {
            if (!$request->user()->can($permission)) {
                return response()->json([
                    'message' => 'Unauthorized. Missing required permission.',
                    'required_permission' => $permission,
                    'your_permissions' => $request->user()->getAllPermissions()->pluck('name')
                ], 403);
            }
        }

        return $next($request);
    }
}
