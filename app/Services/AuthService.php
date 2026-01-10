<?php

namespace App\Services;

class AuthService
{
    public function login(array $credentials)
    {
        // Attempt authentication
        $token = auth()->attempt($credentials);

        if (!$token) {
            throw new \Exception('Invalid username or password');
        }

        $user = auth()->user();

        // Check if user is active
        if (!$user->is_active) {
            auth()->logout();
            throw new \Exception('Your account has been deactivated');
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => $user->load(['admin', 'staff.poly']),
        ];
    }

    public function refreshToken()
    {
        try {
            // Refresh the token
            $newToken = auth()->refresh();

            return [
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
            ];
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            throw new \Exception('Token has expired and cannot be refreshed. Please login again.');
        } catch (\Exception $e) {
            throw new \Exception('Could not refresh token: ' . $e->getMessage());
        }
    }

    public function me()
    {
        $user = auth()->user();

        if (!$user) {
            throw new \Exception('User not authenticated');
        }

        return $user->load(['admin', 'staff.poly']);
    }

    public function logout()
    {
        try {
            auth()->logout();
        } catch (\Exception $e) {
            throw new \Exception('Logout failed: ' . $e->getMessage());
        }
    }
}