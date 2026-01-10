<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->is('api/*')) {
            // Handle JWT Exceptions
            if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token has expired',
                    'hint' => 'Please login again or use the refresh token endpoint',
                ], 401);
            }

            if ($e instanceof TokenInvalidException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token is invalid',
                    'hint' => 'The token signature is incorrect or has been tampered with',
                ], 401);
            }

            if ($e instanceof JWTException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token authentication failed',
                    'hint' => 'Please provide a valid JWT token in the Authorization header',
                ], 401);
            }

            if ($e instanceof UnauthorizedHttpException) {
                $message = $e->getMessage();

                // Handle "Wrong number of segments" error
                if (str_contains($message, 'Wrong number of segments')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid token format',
                        'hint' => 'JWT tokens must have 3 parts separated by dots (header.payload.signature)',
                    ], 401);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'hint' => $message ?: 'Please provide valid authentication credentials',
                ], 401);
            }

            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                ], 404);
            }

            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Endpoint not found',
                ], 404);
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
        }

        return parent::render($request, $e);
    }
}