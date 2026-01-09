<?php

namespace App\Services;

class AuthService
{
    public function login($credentials)
    {
        if (!$token = auth()->attempt($credentials)) {
            throw new \Exception('Invalid credentials');
        }

        $user = auth()->user();
        
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
            $newToken = auth()->refresh();
            
            return [
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Could not refresh token');
        }
    }

    public function logout()
    {
        auth()->logout();
    }

    public function me()
    {
        return auth()->user()->load(['admin', 'staff.poly']);
    }
}