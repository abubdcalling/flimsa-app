<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class RejectRefreshTokens
{
    /**
     * Prevent refresh tokens from authenticating protected endpoints.
     * Assumes this middleware runs AFTER auth:api (so a token exists).
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // We already have a valid token (auth:api passed); just inspect payload
            $payload = JWTAuth::parseToken()->getPayload();

            if (($payload['typ'] ?? 'access') === 'refresh') {
                return response()->json([
                    'success' => false,
                    'message' => 'Refresh token not allowed for this endpoint.'
                ], 401);
            }
        } catch (JWTException $e) {
            // If token can’t be parsed, let auth:api (or global handlers) deal with it
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token.'
            ], 401);
        }

        return $next($request);
    }
}
