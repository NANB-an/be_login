<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
        {
            $token = $request->cookie('access_token') ?? $request->bearerToken();
            
            if (!$token) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            // Check if token is valid
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$accessToken || !$accessToken->can('*')) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            
            // Authenticate user
            auth()->setUser($accessToken->tokenable);
            
            return $next($request);
        }
}
