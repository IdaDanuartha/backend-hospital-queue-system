<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException $e, $request) {
            if ($request->is('api/*')) {
                $message = $e->getMessage();

                // Handle "Wrong number of segments" error (token null or malformed)
                if (str_contains($message, 'Wrong number of segments')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token not provided or invalid format',
                        'hint' => 'Please provide a valid JWT token in the Authorization header (Bearer token)',
                    ], 401);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'hint' => $message ?: 'Please provide valid authentication credentials',
                ], 401);
            }
        });

        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token has expired',
                    'hint' => 'Please login again or use the refresh token endpoint',
                ], 401);
            }
        });

        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token is invalid',
                    'hint' => 'The token signature is incorrect or has been tampered with',
                ], 401);
            }
        });

        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\JWTException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token error',
                    'hint' => $e->getMessage() ?: 'Please provide a valid JWT token',
                ], 401);
            }
        });
    })->create();
