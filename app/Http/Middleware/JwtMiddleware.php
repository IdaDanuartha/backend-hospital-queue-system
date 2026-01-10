<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Check if Authorization header exists
            $authHeader = $request->header('Authorization');

            if (!$authHeader) {
                return $this->unauthorizedResponse(
                    'Authorization header not found',
                    'Please provide a Bearer token in the Authorization header'
                );
            }

            // Check if token is in correct format (Bearer {token})
            if (!str_starts_with($authHeader, 'Bearer ')) {
                return $this->unauthorizedResponse(
                    'Invalid authorization header format',
                    'Authorization header must be in format: Bearer {token}'
                );
            }

            // Extract token
            $token = substr($authHeader, 7);

            // Check if token is empty
            if (empty($token) || trim($token) === '') {
                return $this->unauthorizedResponse(
                    'Token is empty',
                    'Please provide a valid JWT token'
                );
            }

            // Validate JWT format (must have 3 parts separated by dots)
            $tokenParts = explode('.', $token);
            if (count($tokenParts) !== 3) {
                return $this->unauthorizedResponse(
                    'Malformed token',
                    'JWT tokens must have 3 parts separated by dots (header.payload.signature). Your token has ' . count($tokenParts) . ' part(s).'
                );
            }

            // Validate each part is not empty
            $partNames = ['header', 'payload', 'signature'];
            foreach ($tokenParts as $index => $part) {
                if (empty(trim($part))) {
                    return $this->unauthorizedResponse(
                        'Invalid token structure',
                        'The ' . $partNames[$index] . ' part of the JWT token is empty'
                    );
                }
            }

            // Set the token manually to avoid parsing errors
            JWTAuth::setToken($token);

            // Now authenticate the user
            $user = JWTAuth::authenticate();

            if (!$user) {
                return $this->unauthorizedResponse(
                    'User not found',
                    'The token is valid but the user no longer exists',
                    404
                );
            }

            // Check if user is active
            if (!$user->is_active) {
                return $this->forbiddenResponse(
                    'Account is inactive',
                    'Your account has been deactivated. Please contact administrator.'
                );
            }

        } catch (TokenExpiredException $e) {
            return $this->unauthorizedResponse(
                'Token has expired',
                'Please login again or use the refresh token endpoint to get a new token'
            );

        } catch (TokenInvalidException $e) {
            return $this->unauthorizedResponse(
                'Token is invalid',
                'The token signature is incorrect or has been tampered with'
            );

        } catch (JWTException $e) {
            return $this->unauthorizedResponse(
                'Token authentication failed',
                $e->getMessage()
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication error',
                'hint' => 'An unexpected error occurred during authentication',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }

        return $next($request);
    }

    private function unauthorizedResponse(string $message, string $hint, int $code = 401)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'hint' => $hint
        ], $code);
    }

    private function forbiddenResponse(string $message, string $hint)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'hint' => $hint
        ], 403);
    }
}
