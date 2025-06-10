<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        \Log::info('RoleMiddleware: User authenticated: ' . (auth()->check() ? 'Yes' : 'No'));
        \Log::info('RoleMiddleware: User role: ' . (auth()->user() ? auth()->user()->role : 'None'));
        \Log::info('RoleMiddleware: Required roles: ' . implode(', ', $roles));

        if (!auth()->check() || !in_array(auth()->user()->role, $roles)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return $next($request);
    }
}
