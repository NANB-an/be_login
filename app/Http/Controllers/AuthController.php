<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cookie;
// Events are kept for scalability but noted as optional only included for practice
// use Illuminate\Auth\Events\Registered;
// use Illuminate\Auth\Events\Login;
// use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Event kept for future extensibility (e.g., email verification)
        // but not strictly necessary for basic registration
        // event(new Registered($user));

        // Auto-login after registration
        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(15))->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['refresh'], now()->addDays(7))->plainTextToken;

        $user->last_login_at = now();
        $user->save();

        // Login event kept for potential future logging/analytics
        // but not required for core functionality
        // event(new Login('sanctum', $user, false));

        $response = response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'access_token' => $token,
            'expires_in' => 15 * 60,
            'refresh_token' => $refreshToken
        ]);

        $response->cookie('access_token', $token, 15, '/', null, true, true);
        $response->cookie('refresh_token', $refreshToken, 7 * 24 * 60, '/', null, true, true);

        return $response;
    }

    public function login(Request $request)
    {
        // Rate limiting
        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Too many login attempts. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'remember_me' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Check if account is locked
        if ($user && $user->isLocked()) {
            return response()->json([
                'message' => 'Account temporarily locked. Please try again later.',
                'locked_until' => $user->locked_until
            ], 423);
        }

        // Check credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key);
            
            if ($user) {
                $user->incrementLoginAttempts();
            }

            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Reset rate limiting and login attempts
        RateLimiter::clear($key);
        $user->resetLoginAttempts();
        $user->last_login_at = now();
        $user->save();

        // Generate tokens
        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(15))->plainTextToken;
        
        if ($request->remember_me) {
            $refreshToken = $user->createToken('refresh_token', ['refresh'], now()->addDays(7))->plainTextToken;
        }

        // Login event kept for potential future logging/analytics
        // but not required for core functionality
        // event(new Login('sanctum', $user, false))

        $response = response()->json([
            'user' => $user,
            'access_token' => $token,
            'expires_in' => 15 * 60,
            'refresh_token' => $refreshToken ?? null
        ]);

        // Set HttpOnly cookies
        $response->cookie('access_token', $token, 15, '/', null, true, true);
        if (isset($refreshToken)) {
            $response->cookie('refresh_token', $refreshToken, 7 * 24 * 60, '/', null, true, true);
        }

        return $response;
    }

    public function logout(Request $request)
        {
            $token = $request->user()?->currentAccessToken();
            if ($token) {
                $token->delete();
            }
            // Logout event kept for potential future cleanup tasks
            // but not required for core functionality
            // event(new Logout('sanctum', $request->user()));

            return response()->json(['message' => 'Logged out successfully'])
                ->withoutCookie('access_token')
                ->withoutCookie('refresh_token');
        }

    public function refreshToken(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token') || $request->bearerToken();
        
        if (!$refreshToken) {
            return response()->json(['message' => 'Refresh token required'], 401);
        }

        $token = \Laravel\Sanctum\PersonalAccessToken::findToken($refreshToken);
        
        if (!$token || !$token->can('refresh')) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $user = $token->tokenable;
        
        // Delete old refresh token
        $token->delete();

        // Create new tokens
        $newAccessToken = $user->createToken('auth_token', ['*'], now()->addMinutes(15))->plainTextToken;
        $newRefreshToken = $user->createToken('refresh_token', ['refresh'], now()->addDays(7))->plainTextToken;

        $response = response()->json([
            'access_token' => $newAccessToken,
            'expires_in' => 15 * 60,
            'refresh_token' => $newRefreshToken
        ]);

        $response->cookie('access_token', $newAccessToken, 15, '/', null, true, true);
        $response->cookie('refresh_token', $newRefreshToken, 7 * 24 * 60, '/', null, true, true);

        return $response;
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}